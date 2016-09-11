<?php

namespace App\AccountancyModule\TravelModule;

use Model\Travel\VehicleNotFoundException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class VehiclePresenter extends BasePresenter {

     /**
     *
     * @var \Model\TravelService
     */
    protected $travelService;

    public function __construct(\Model\TravelService $ts) {
        parent::__construct();
        $this->travelService = $ts;
    }

    public function renderDefault() {
        $this->template->list = $this->travelService->getAllVehicles($this->unit->ID);
    }

	/**
	 * @param string $id
	 * @return \Model\Travel\Vehicle
	 * @throws BadRequestException
	 */
    private function getVehicle($id)
	{
		try {
			$vehicle = $this->travelService->getVehicle($id);
		} catch(VehicleNotFoundException $e) {
			throw new BadRequestException('Zadané vozidlo neexistuje', 404);
		}

		// Check whether vehicle belongs to unit
		if($vehicle->getUnitId() != $this->unit->ID) {
			$this->flashMessage('Nemáte oprávnění k vozidlu', 'danger');
			$this->redirect('default');
		}
		return $vehicle;
	}

    public function actionDetail($id)
	{
		$this->template->vehicle = $this->getVehicle($id);
	}

    public function renderDetail($id) {
        $this->template->commands = $this->travelService->getAllCommandsByVehicle($this->unit->ID, $id);
    }

    public function handleRemove($vehicleId) {
		// Check whether vehicle exists and belongs to unit
    	$this->getVehicle($vehicleId);

        if ($this->travelService->removeVehicle($vehicleId, $this->unit->ID)) {
            $this->flashMessage("Vozidlo bylo odebráno.");
        } else {
            $this->flashMessage("Nelze smazat vozidlo s cestovními příkazy.", "warning");
        }
        $this->redirect("this");
    }

    protected function makeVehicleForm($name) {
        $form = $this->prepareForm($this, $name);
        $form->addText("type", "Typ*")
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte vyplnit typ.");
        $form->addText("registration", "SPZ*")
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte vyplnit SPZ.");
        $form->addText("consumption", "Průměrná spotřeba*")
                ->setAttribute("class", "form-control")
                ->addRule(Form::FILLED, "Musíte vyplnit průměrnou spotřebu.")
                ->addRule(Form::FLOAT, "Průměrná spotřeba musí být číslo!");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function createComponentFormCreateVehicle($name) {
        $form = $this->makeVehicleForm($name);
        $form->addSubmit('send', 'Založit')
                ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    function formCreateVehicleSubmitted(Form $form) {
        $v = $form->getValues();
        $v['unit_id'] = $this->unit->ID;
        $v['consumption'] = str_replace(",", ".", $v['consumption']);
        $this->travelService->addVehicle($v);
        $this->flashMessage("Záznam o vozidle byl založen.");
        $this->redirect("this");
    }

}
