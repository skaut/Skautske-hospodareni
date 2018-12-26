<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\VehicleGrid;
use App\AccountancyModule\TravelModule\Factories\IVehicleGridFactory;
use App\Forms\BaseForm;
use Model\BaseService;
use Model\DTO\Travel\Vehicle as VehicleDTO;
use Model\Travel\Commands\Vehicle\CreateVehicle;
use Model\TravelService;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Security\Identity;
use Nette\Utils\ArrayHash;
use function array_key_exists;

class VehiclePresenter extends BasePresenter
{
    /** @var TravelService */
    private $travelService;

    /** @var IVehicleGridFactory */
    private $gridFactory;

    public function __construct(TravelService $travelService, IVehicleGridFactory $gridFactory)
    {
        parent::__construct();
        $this->travelService = $travelService;
        $this->gridFactory   = $gridFactory;
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
            $this->redirect('default');
        }
        return $vehicle;
    }

    private function isVehicleEditable(?VehicleDTO $vehicle) : bool
    {
        /** @var Identity $identity */
        $identity = $this->getUser()->getIdentity();

        $unitAccessible = array_key_exists($vehicle->getUnitId(), $identity->access[BaseService::ACCESS_EDIT]) ||
        $vehicle->getSubunitId() !== null && array_key_exists($vehicle->getSubunitId(), $identity->access[BaseService::ACCESS_EDIT]);

        return $vehicle !== null && $unitAccessible;
    }

    public function renderDetail(int $id) : void
    {
        try {
            $vehicle = $this->getVehicle($id);

            $subUnitName = null;
            if ($vehicle->getSubunitId() !== null) {
                $subUnitName = $this->unitService->getDetail($vehicle->getSubunitId())->SortName;
            }

            $this->template->setParameters([
                'vehicle' => $vehicle,
                'subunitName' => $subUnitName,
                'canDelete' => $this->travelService->getCommandsCount($id) === 0,
                'commands' => $this->travelService->getAllCommandsByVehicle($id),
                'isEditable' => $this->isVehicleEditable ($vehicle),
            ]);
        } catch (BadRequestException $exc) {
            $this->flashMessage($exc->getMessage(), 'danger');
            $this->redirect('default');
        }
    }

    public function handleRemove(int $vehicleId) : void
    {
        // Check whether vehicle exists and belongs to unit
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->flashMessage('K vozidlu nemáte oprávnění přistupovat!', 'danger');
            $this->redirect('default');
        };

        if ($this->travelService->removeVehicle($vehicleId)) {
            $this->flashMessage('Vozidlo bylo odebráno.');
        } else {
            $this->flashMessage('Nelze smazat vozidlo s cestovními příkazy.', 'warning');
        }
        $this->redirect('default');
    }

    public function handleArchive(int $vehicleId) : void
    {
        // Check whether vehicle exists and belongs to unit
        $vehicle = $this->getVehicle($vehicleId);
        if (! $this->isVehicleEditable($vehicle)) {
            $this->flashMessage('K vozidlu nemáte oprávnění přistupovat!', 'danger');
            $this->redirect('default');
        };

        $this->travelService->archiveVehicle($vehicleId);
        $this->flashMessage('Vozidlo bylo archivováno', 'success');

        $this->redirect('this');
    }


    protected function createComponentGrid() : VehicleGrid
    {
        return $this->gridFactory->create($this->getUnitId());
    }

    protected function createComponentFormCreateVehicle() : Form
    {
        $form = new BaseForm();
        $form->addText('type', 'Typ*')
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
        $form->addSubmit('send', 'Založit');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) : void {
            $this->formCreateVehicleSubmitted($values);
        };

        return $form;
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
        $this->redirect('default');
    }
}
