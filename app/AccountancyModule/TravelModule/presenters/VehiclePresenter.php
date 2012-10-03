<?php

/**
 * @author sinacek
 */
class Accountancy_Travel_VehiclePresenter extends Accountancy_Travel_BasePresenter {

    function startup() {
        parent::startup();
//        $contractId = $this->getParameter("contractId", NULL);
//        $this->template->Vehicle = $Vehicle = $this->context->travelService->getVehicle($contractId);
//        $this->template->isEditable = $this->isEditable = ($VehicleId === NULL || $this->unit->ID == $Vehicle->unit_id) ? true : false;
//        if (!$this->isEditable) {
//            $this->flashMessage("Neoprávněný přístup k cestovní smlouvě.", "danger");
//            $this->redirect("Default:");
//        }
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllVehicles($this->unit->ID);
    }

    public function renderDetail($vehicleId) {
        $this->template->vehicle = $contract = $this->context->travelService->getVehicle($vehicleId, true);
        $this->template->commands = $this->context->travelService->getAllCommandsByVehicle($this->unit->ID, $vehicleId);
    }

    public function handleRemove($vehicleId) {
        $this->context->travelService->removeVehicle($vehicleId);
        $this->flashMessage("Vozidlo bylo odebráno.");
        $this->redirect("this");
    }

    protected function makeVehicleForm($name) {
        $form = new AppForm($this, $name);
        $form->addText("type", "Typ*")
                ->addRule(Form::FILLED, "Musíte vyplnit typ.");
        $form->addText("spz", "SPZ*")
                ->addRule(Form::FILLED, "Musíte vyplnit SPZ.");
        $form->addText("consumption", "Průměrná spotřeba*")
                ->addRule(Form::FILLED, "Musíte vyplnit průměrnou spotřebu.");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function createComponentFormCreateVehicle($name) {
        $form = $this->makeVehicleForm($name);
        $form->addSubmit('send', 'Založit')
                ->getControlPrototype()->setClass("btn btn-primary");
        return $form;
    }

    function formCreateVehicleSubmitted(AppForm $form) {
        $v = $form->getValues();
        $v['unit_id'] = $this->unit->ID;
        if ($this->context->travelService->addVehicle($v, $this->unit))
            $this->flashMessage("Záznam o vozidle byl založen.");
        else
            $this->flashMessage("Záznam o vozidle se nepodařilo založit.", "danger");
        $this->redirect("this");
    }

}
