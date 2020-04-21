<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\RoadworthyControl;
use App\AccountancyModule\TravelModule\Factories\IRoadworthyControlFactory;
use App\Forms\BaseForm;
use Model\Common\File;
use Model\DTO\Travel\Vehicle as VehicleDTO;
use Model\Travel\Commands\Vehicle\CreateVehicle;
use Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use Model\TravelService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Ublaboo\Responses\PSR7StreamResponse;
use function assert;
use function in_array;

class VehiclePresenter extends BasePresenter
{
    /**
     * @var int
     * @persistent
     */
    public $id = 0;

    /** @var TravelService */
    private $travelService;

    /** @var IRoadworthyControlFactory */
    private $roadworthyControlFactory;

    public function __construct(TravelService $travelService, IRoadworthyControlFactory $roadworthyControlFactory)
    {
        parent::__construct();
        $this->travelService            = $travelService;
        $this->roadworthyControlFactory = $roadworthyControlFactory;
        $this->setLayout('layout.new');
    }

    /**
     * @throws BadRequestException
     * @throws AbortException
     */
    private function getVehicle(int $id) : VehicleDTO
    {
        $vehicle = $this->travelService->getVehicleDTO($id);
        if ($vehicle === null) {
            throw new BadRequestException('Zadané vozidlo neexistuje');
        }

        // Check whether vehicle belongs to unit
        if ($vehicle->getUnitId() !== $this->officialUnit->getId()) {
            $this->flashMessage('Nemáte oprávnění k vozidlu', 'danger');
            $this->redirect('VehicleList:default');
        }

        return $vehicle;
    }

    public function actionDownloadScan(int $id, string $path) : void
    {
        $this->getVehicle($id); // Check access

        foreach ($this->queryBus->handle(new RoadworthyScansQuery($id)) as $scan) {
            assert($scan instanceof File);

            if ($scan->getPath() !== $path) {
                continue;
            }

            $this->sendResponse(new PSR7StreamResponse($scan->getContents(), $scan->getFileName()));
        }

        throw new BadRequestException('Scan not found', IResponse::S404_NOT_FOUND);
    }

    public function renderDetail(int $id) : void
    {
        try {
            $vehicle = $this->getVehicle($id);

            $subUnitName = null;
            try {
                if ($vehicle->getSubunitId() !== null) {
                    $unit = $this->queryBus->handle(new UnitQuery($vehicle->getSubunitId()));
                    assert($unit instanceof Unit);
                    $subUnitName = $unit->getSortName();
                }
            } catch (UnitNotFound $exc) {
                // jednotka může být smazaná a pak na ní nikdo nemá oprávnění
            }

            $this->template->setParameters([
                'vehicle' => $vehicle,
                'subunitName' => $subUnitName,
                'canDelete' => $this->travelService->getCommandsCount($id) === 0,
                'commands' => $this->travelService->getAllCommandsByVehicle($id),
                'isEditable' => $this->isVehicleEditable($vehicle),
            ]);
        } catch (BadRequestException $exc) {
            $this->flashMessage($exc->getMessage(), 'danger');
            $this->redirect('VehicleList:default');
        }
    }

    public function handleRemove(int $vehicleId) : void
    {
        // Check whether vehicle exists and belongs to unit
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->setView('accessDenied');

            return;
        }

        if ($this->travelService->removeVehicle($vehicleId)) {
            $this->flashMessage('Vozidlo bylo odebráno.');
        } else {
            $this->flashMessage('Nelze smazat vozidlo s cestovními příkazy.', 'warning');
        }
        $this->redirect('VehicleList:default');
    }

    public function handleArchive(int $vehicleId) : void
    {
        // Check whether vehicle exists and belongs to unit
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->setView('accessDenied');

            return;
        }

        $this->travelService->archiveVehicle($vehicleId);
        $this->flashMessage('Vozidlo bylo archivováno', 'success');

        $this->redirect('this');
    }

    protected function createComponentFormCreateVehicle() : Form
    {
        $form = new BaseForm();

        $form->addText('type', 'Typ')
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit typ.');
        $form->addText('registration', 'SPZ*')
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit SPZ.');
        $form->addText('consumption', 'Harmonizovaná spotřeba*')
            ->setAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit průměrnou spotřebu.')
            ->addRule(Form::FLOAT, 'Průměrná spotřeba musí být číslo!');
        $form->addSelect('subunitId', 'Oddíl', $this->unitService->getSubunitPairs($this->getUnitId()))
            ->setAttribute('class', 'form-control')
            ->setPrompt('Žádný')
            ->setRequired(false);
        $form->addSubmit('send', 'Založit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) : void {
            $this->formCreateVehicleSubmitted($values);
        };

        return $form;
    }

    protected function createComponentRoadworthy() : RoadworthyControl
    {
        return $this->roadworthyControlFactory->create(
            $this->id,
            $this->isVehicleEditable($this->getVehicle($this->id)),
        );
    }

    private function formCreateVehicleSubmitted(ArrayHash $values) : void
    {
        $this->commandBus->handle(
            new CreateVehicle(
                $values->type,
                $this->getUnitId(),
                $values->subunitId,
                $values->registration,
                $values->consumption,
                $this->getUser()->getId()
            )
        );

        $this->flashMessage('Vozidlo bylo vytvořeno');
        $this->redirect('VehicleList:default');
    }

    private function isVehicleEditable(?VehicleDTO $vehicle) : bool
    {
        if ($vehicle === null) {
            return false;
        }

        $editableUnitIds = $this->getEditableUnitIds();

        return in_array($vehicle->getUnitId(), $editableUnitIds, true)
            || in_array($vehicle->getSubunitId(), $editableUnitIds, true);
    }
}
