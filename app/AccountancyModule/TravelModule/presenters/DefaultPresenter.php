<?php

/**
 * @author Hána František
 */
class Accountancy_Travel_DefaultPresenter extends Accountancy_Travel_BasePresenter {

    function startup() {
        parent::startup();
    }
    
    protected function isEditable($id){
        $this->template->command = $command = $this->context->travelService->getCommand($id);
        $this->template->contract = $contract = $this->context->travelService->getContract($command->contract_id);
        return ($this->unit->ID == $contract->unit_id && $command->closed == NULL) ? true : false;
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllCommands($this->unit->ID);
    }

    public function renderDetail($id) {
        if ($id == NULL) {
            $this->redirect("default");
        }
        $this->template->command = $command = $this->context->travelService->getCommand($id);
        $this->template->contract = $contract = $this->context->travelService->getContract($command->contract_id);
        $this->template->isEditable = $this->isEditable = ($this->unit->ID == $contract->unit_id && $command->closed == NULL) ? true : false;
        $this->template->travels = $this->context->travelService->getTravels($command->id);
        $this['formAddTravel']->setDefaults(array("command_id" => $command->id));
    }

    function renderEditCommand($commandId) {
        $defaults = $this->context->travelService->getCommand($commandId);
        $defaults['id'] = $commandId;
        $form = $this['formEditCommand'];
        $form->setDefaults($defaults);
        $this->template->form = $form;
    }

    public function actionPrint($commandId) {
        $template = $this->template;
        $template->registerHelperLoader("AccountancyHelpers::loader");
        $template->setFile(dirname(__FILE__) . '/../templates/Default/ex.command.latte');
        $template->command = $command = $this->context->travelService->getCommand($commandId);
        $template->contract = $this->context->travelService->getContract($command->contract_id);
        $template->travels = $travels = $this->context->travelService->getTravels($command->id);
        if (!empty($travels)) {
            $template->end = end($travels);
            $template->start = reset($travels);
        }
        //        echo $template;die();
        $this->context->travelService->makePdf($template, "cestovni-prikaz.pdf");
        $this->terminate();
    }

    public function handleCloseCommand($commandId) {
        $command = $this->context->travelService->getCommand($commandId);
        if (!$this->context->travelService->isMyContract($command->contract_id, $this->unit)) {
            $this->flashMessage("Nemáte právo uzavřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }
//        $who = $this->context->userService->getPersonalDetail()->Email;
        $this->context->travelService->closeCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl uzavřen.");
        $this->redirect("this");
    }

    public function handleOpenCommand($commandId) {
        $command = $this->context->travelService->getCommand($commandId);
        if (!$this->context->travelService->isMyContract($command->contract_id, $this->unit)) {
            $this->flashMessage("Nemáte právo otevřít cestovní příkaz.", "danger");
            $this->redirect("default");
        }
//        $who = $this->context->userService->getPersonalDetail()->Email;
        $this->context->travelService->openCommand($commandId);
        $this->flashMessage("Cestovní příkaz byl otevřen.");
        $this->redirect("this");
    }

    public function handleRemoveTravel($travelId) {
        $travel = $this->context->travelService->getTravel($travelId);
        $command = $this->context->travelService->getCommand($travel->command_id);
        $contract = $this->context->travelService->getContract($command->contract_id);
        if ($this->unit->ID == $contract->unit_id && $command->closed == NULL) {
            $this->context->travelService->deleteTravel($travelId);
            $this->flashMessage("Cesta z \"$travel->start_place\" do \"$travel->end_place\" byla smazána.");
        } else {
            $this->flashMessage("Nemáte oprávnění smazat cestu.", "danger");
        }
        $this->redirect("this");
    }

    public function handleRemoveCommand($commandId) {
        $command = $this->context->travelService->getCommand($commandId);
        $contract = $this->context->travelService->getContract($command->contract_id);

        if ($this->unit->ID == $contract->unit_id) {
            $this->context->travelService->deleteCommand($commandId);
            $this->flashMessage("Cestovní příkaz byl smazán.");
        } else {
            $this->flashMessage("Nemáte oprávnění smazat cestovní příkaz.", "danger");
        }
        $this->redirect("this");
    }

    protected function makeCommandForm($name) {
        $contracts = $this->context->travelService->getAllContractsPairs($this->unit->ID);
        $vehicles = $this->context->travelService->getVehiclesPairs($this->unit->ID);

        $form = new AppForm($this, $name);
        $form->addSelect("contract_id", "Smlouva", $contracts)
                ->setPrompt("Vyberte smlouvu")
                ->addRule(Form::FILLED, "Musíte vybrat smlouvu");
        $form->addText("purpose", "Účel cesty*")
                ->addRule(Form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addText("passengers", "Spolucestující");
        $form->addSelect("vehicle_id", "Vozidlo*", $vehicles)
                ->setPrompt("Vyberte vozidlo")
                ->addRule(Form::FILLED, "Musíte vyplnit typ vozidla.");
        $form->addText("fuel_price", "Cena paliva za 1l*")
                ->addRule(Form::FILLED, "Musíte vyplnit cenu paliva.")
                ->addRule(Form::FLOAT, "Musíte zadat desetinné číslo.");
        $form->addText("amortization", "Opotřebení*")
                ->addRule(Form::FILLED, "Musíte vyplnit opotřebení.")
                ->addRule(Form::FLOAT, "Musíte zadat desetinné číslo.");
        $form->addText("note", "Poznámka", NULL, 64);
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function createComponentFormCreateCommand($name) {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Založit')
                ->getControlPrototype()->setClass("btn btn-primary");
        return $form;
    }

    function formCreateCommandSubmitted(AppForm $form) {
        $v = $form->getValues();
//        $v['state'] = "open";
        if ($this->context->travelService->addCommand($v, $this->unit))
            $this->flashMessage("Cestovní příkaz byl založen.");
        else
            $this->flashMessage("Cestovní příkaz se nepodařilo založit.", "danger");
        $this->redirect("this");
    }

    function createComponentFormEditCommand($name) {
        $form = $this->makeCommandForm($name);
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->addHidden("id");
        return $form;
    }

    function formEditCommandSubmitted(AppForm $form) {
        $v = $form->getValues();
        $id = $v['id'];
        unset($v['id']);
        if ($this->context->travelService->updateCommand($v, $this->unit, $id))
            $this->flashMessage("Cestovní příkaz byl upraven.");
        else
            $this->flashMessage("Cestovní příkaz se nepodařilo upravit.", "danger");
        $this->redirect("detail", array("id" => $id));
    }

    public function createComponentFormAddTravel($name) {
        $form = new AppForm($this, $name);
        $form->getElementPrototype()->class("form-inline");
        $form->addHidden("command_id");
        $form->addDatePicker("start_date", "Datum cesty")
                ->addRule(Form::FILLED, "Musíte vyplnit datum cesty.")
                ->getControlPrototype()->class("input-small");
        $form->addText("start_place", "Z*")
                ->addRule(Form::FILLED, "Musíte vyplnit místo počátku cesty.")
                ->getControlPrototype()->class("input-medium");
        $form->addText("end_place", "Do*")
                ->addRule(Form::FILLED, "Musíte vyplnit místo konce cesty.")
                ->getControlPrototype()->class("input-medium");
        $form->addText("distance", "Vzdálenost*")
                ->addRule(Form::FILLED, "Musíte vyplnit vzdálenost.")
                ->getControlPrototype()->class("input-mini");
        $form->addSubmit('send', 'Přidat')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formAddTravelSubmitted(AppForm $form) {
        $v = $form->getValues();
        if(!$this->isEditable($v->command_id)){
            $this->flashMessage("Nelze upravovat cestovní příkaz.", "danger");
            $this->redirect("this");
        }
            
        $commandId = $v['command_id'];
        $command = $this->context->travelService->getCommand($commandId);
        if (!$this->context->travelService->isMyContract($command->contract_id, $this->unit)) {
            $this->flashMessage("Nemáte právo přidat cestu k cestovnímu příkazu.", "danger");
            $this->redirect("this");
        }
        $this->context->travelService->addTravel($v);
        $this->flashMessage("Cesta byla přidána.");
        $this->redirect("this");
    }

}
