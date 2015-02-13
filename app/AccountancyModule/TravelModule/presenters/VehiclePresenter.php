<?php

namespace App\AccountancyModule\TravelModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
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
        if ($this->context->travelService->removeVehicle($vehicleId, $this->unit->ID)) {
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
        $this->context->travelService->addVehicle($v);
        $this->flashMessage("Záznam o vozidle byl založen.");
        $this->redirect("this");
    }

}
