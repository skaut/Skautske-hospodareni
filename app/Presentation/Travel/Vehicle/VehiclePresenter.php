<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Vehicle;

use App\Model\Common\File;
use App\Model\DTO\Travel\Vehicle as VehicleDTO;
use App\Model\Travel\Commands\Vehicle\CreateVehicle;
use App\Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use App\Model\Travel\TravelService;
use App\Model\Travel\VehicleLinkedRecord;
use App\Model\Travel\VehicleNotFound;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;
use App\Presentation\Travel\TravelBasePresenter;
use App\Presentation\Travel\Vehicle\Components\RoadworthyControl;
use App\Presentation\Travel\Vehicle\Factories\IRoadworthyControlFactory;
use Component\Forms\BaseForm;
use Contributte\Application\Response\PSR7StreamResponse;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;

use function assert;
use function in_array;

class VehiclePresenter extends TravelBasePresenter
{
    /** @persistent */
    public int $id = 0;

    public function __construct(private TravelService $travelService, private IRoadworthyControlFactory $roadworthyControlFactory)
    {
        parent::__construct();
    }

    /**
     * @throws AbortException
     * @throws BadRequestException
     */
    private function getVehicle(int $id): VehicleDTO
    {
        $vehicle = $this->travelService->getVehicleDTO($id);
        if ($vehicle === null) {
            throw new BadRequestException('Zadané vozidlo neexistuje');
        }

        if ($vehicle->getUnitId() !== $this->officialUnit->getId()) {
            $this->flashMessage('Nemáte oprávnění k vozidlu', 'danger');
            $this->redirect('VehicleList:default');
        }

        return $vehicle;
    }

    public function actionDownloadScan(int $id, string $path): void
    {
        $this->getVehicle($id);

        foreach ($this->queryBus->handle(new RoadworthyScansQuery($id)) as $scan) {
            assert($scan instanceof File);

            if ($scan->getPath() !== $path) {
                continue;
            }

            $this->sendResponse(new PSR7StreamResponse($scan->getContents(), $scan->getFileName()));
        }

        throw new BadRequestException('Scan not found', IResponse::S404_NotFound);
    }

    public function renderDetail(int $id): void
    {
        try {
            $vehicle = $this->getVehicle($id);

            $subunitName = null;
            try {
                if ($vehicle->getSubunitId() !== null) {
                    $unit = $this->queryBus->handle(new UnitQuery($vehicle->getSubunitId()));
                    assert($unit instanceof Unit);
                    $subunitName = $unit->getSortName();
                }
            } catch (UnitNotFound) {
            }

            $this->template->setParameters([
                'vehicle' => $vehicle,
                'subunitName' => $subunitName,
                'canDelete' => $this->travelService->getCommandsCount($id) === 0,
                'commands' => $this->travelService->getAllCommandsByVehicle($id),
                'isEditable' => $this->isVehicleEditable($vehicle),
            ]);
        } catch (BadRequestException $exc) {
            $this->flashMessage($exc->getMessage(), 'danger');
            $this->redirect('VehicleList:default');
        }
    }

    public function handleRemove(int $vehicleId): void
    {
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->setView('accessDenied');

            return;
        }

        try {
            $this->travelService->removeVehicle($vehicleId);
            $this->flashMessage('Vozidlo bylo odebráno.');
        } catch (VehicleLinkedRecord) {
            $this->flashMessage('Nelze smazat vozidlo s cestovními příkazy.', 'warning');
        } catch (VehicleNotFound) {
            $this->flashMessage('Vozidlo nebylo nalezeno', 'warning');
        }

        $this->redirect('VehicleList:default');
    }

    public function handleArchive(int $vehicleId): void
    {
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->setView('accessDenied');

            return;
        }

        $this->travelService->archiveVehicle($vehicleId);
        $this->flashMessage('Vozidlo bylo archivováno', 'success');

        $this->redirect('this');
    }

    protected function createComponentFormCreateVehicle(): Form
    {
        $form = new BaseForm();

        $form->addText('type', 'Typ')
            ->setHtmlAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit typ.');
        $form->addText('registration', 'SPZ*')
            ->setHtmlAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit SPZ.');
        $form->addText('consumption', 'Harmonizovaná spotřeba*')
            ->setHtmlAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Musíte vyplnit průměrnou spotřebu.')
            ->addRule(Form::FLOAT, 'Průměrná spotřeba musí být číslo!');
        $form->addSelect('subunitId', 'Oddíl', $this->unitService->getSubunitPairs($this->getUnitId()))
            ->setHtmlAttribute('class', 'form-control')
            ->setPrompt('Žádný')
            ->setRequired(false);
        $form->addSubmit('send', 'Založit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form, ArrayHash $values): void {
            $this->formCreateVehicleSubmitted($values);
        };

        return $form;
    }

    protected function createComponentRoadworthy(): RoadworthyControl
    {
        return $this->roadworthyControlFactory->create(
            $this->id,
            $this->isVehicleEditable($this->getVehicle($this->id)),
        );
    }

    private function formCreateVehicleSubmitted(ArrayHash $values): void
    {
        $this->commandBus->handle(
            new CreateVehicle(
                $values->type,
                $this->getUnitId(),
                $values->subunitId,
                $values->registration,
                $values->consumption,
                $this->getUser()->getId(),
            ),
        );

        $this->flashMessage('Vozidlo bylo vytvořeno');
        $this->redirect('VehicleList:default');
    }

    private function isVehicleEditable(?VehicleDTO $vehicle): bool
    {
        if ($vehicle === null) {
            return false;
        }

        $editableUnitIds = $this->getEditableUnitIds();

        return in_array($vehicle->getUnitId(), $editableUnitIds, true)
            || in_array($vehicle->getSubunitId(), $editableUnitIds, true);
    }
}
