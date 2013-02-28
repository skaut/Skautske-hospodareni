<?php

namespace AccountancyModule\TravelModule;

use Nette\Application\UI\Form;

/**
 * @author sinacek
 */
class VehiclePresenter extends BasePresenter {

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

    protected function isVehicleAccessible($vehicleId) {
        return $this->context->travelService->isVehicleAccessible($vehicleId, $this->unit);
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllVehicles($this->unit->ID);
    }

    public function renderDetail($id) {
        if (!$this->isVehicleAccessible($id)) {
            $this->flashMessage("Nemáte oprávnění k vozidlu", "danger");
            $this->redirect("default");
        }

        $this->template->vehicle = $contract = $this->context->travelService->getVehicle($id, true);
        $this->template->commands = $this->context->travelService->getAllCommandsByVehicle($this->unit->ID, $id);
    }

    public function handleRemove($vehicleId) {
        if (!$this->isVehicleAccessible($vehicleId)) {
            $this->flashMessage("Nemáte oprávnění k vozidlu", "danger");
            $this->redirect("default");
        }
        $this->context->travelService->removeVehicle($vehicleId);
        $this->flashMessage("Vozidlo bylo odebráno.");
        $this->redirect("this");
    }

    protected function makeVehicleForm($name) {
        $form = new Form($this, $name);
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

    function formCreateVehicleSubmitted(Form $form) {
        $v = $form->getValues();
        $v['unit_id'] = $this->unit->ID;
        $this->context->travelService->addVehicle($v);
        $this->flashMessage("Záznam o vozidle byl založen.");
        $this->redirect("this");
    }

}
