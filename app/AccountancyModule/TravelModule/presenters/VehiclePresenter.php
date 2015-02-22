<?php

namespace App\AccountancyModule\TravelModule;

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
    
    protected function isVehicleAccessible($vehicleId) {
        return $this->travelService->isVehicleAccessible($vehicleId, $this->unit);
    }

    public function renderDefault() {
        $this->template->list = $this->travelService->getAllVehicles($this->unit->ID);
    }

    public function renderDetail($id) {
        if (!$this->isVehicleAccessible($id)) {
            $this->flashMessage("Nemáte oprávnění k vozidlu", "danger");
            $this->redirect("default");
        }

        $this->template->vehicle = $contract = $this->travelService->getVehicle($id, true);
        $this->template->commands = $this->travelService->getAllCommandsByVehicle($this->unit->ID, $id);
    }

    public function handleRemove($vehicleId) {
        if (!$this->isVehicleAccessible($vehicleId)) {
            $this->flashMessage("Nemáte oprávnění k vozidlu", "danger");
            $this->redirect("default");
        }
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
        $form->addText("spz", "SPZ*")
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
