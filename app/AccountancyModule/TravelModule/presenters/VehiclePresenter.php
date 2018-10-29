<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\VehicleGrid;
use App\AccountancyModule\TravelModule\Factories\IVehicleGridFactory;
use App\Forms\BaseForm;
use Model\Travel\Commands\Vehicle\CreateVehicle;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFound;
use Model\TravelService;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

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
     */
    private function getVehicle(int $id) : Vehicle
    {
        try {
            $vehicle = $this->travelService->getVehicle($id);
        } catch (VehicleNotFound $e) {
            throw new BadRequestException('Zadané vozidlo neexistuje');
        }

        // Check whether vehicle belongs to unit
        if ($vehicle->getUnitId() !== $this->unit->ID) {
            $this->flashMessage('Nemáte oprávnění k vozidlu', 'danger');
            $this->redirect('default');
        }
        return $vehicle;
    }

    public function actionDetail(int $id) : void
    {
        $vehicle                    = $this->getVehicle($id);
        $this->template->vehicle    = $vehicle;
        $this->template->vehicleDTO = $this->travelService->getVehicleDTO($id);
        if ($vehicle->getSubunitId() !== null) {
            $this->template->subunitName = $this->unitService->getDetail($vehicle->getSubunitId())->SortName;
        }

        $this->template->canDelete = $this->travelService->getCommandsCount($id) === 0;
    }

    public function renderDetail(int $id) : void
    {
        $this->template->commands = $this->travelService->getAllCommandsByVehicle($id);
    }

    public function handleRemove(int $vehicleId) : void
    {
        // Check whether vehicle exists and belongs to unit
        $this->getVehicle($vehicleId);

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
        $this->getVehicle($vehicleId);

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
