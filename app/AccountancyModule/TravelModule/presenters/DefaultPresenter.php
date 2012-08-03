<?php

/**
 * @author Hána František
 */
class Accountancy_Travel_DefaultPresenter extends Accountancy_Travel_BasePresenter {

    function startup() {
        parent::startup();
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllCommands($this->unit->ID);
    }

    public function renderDetail($commandId) {
        $this->template->command = $command = $this->context->travelService->getCommand($commandId);
        $this->template->contract = $contract = $this->context->travelService->getContract($command->contract_id);
        $this->template->travels = $this->context->travelService->getTravels($command->id);
        $this->template->isEditable = ($this->unit->ID == $contract->unit_id && $command->state == "open") ? true : false;
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
        $template->travels = $this->context->travelService->getTravels($command->contract_id);
        $this->context->travelService->makePdf($template, "cestovni-prikaz.pdf");
    }

    public function handleRemoveTravel($travelId) {
        $travel = $this->context->travelService->getTravel($travelId);
        $command = $this->context->travelService->getCommand($travel->command_id);
        $contract = $this->context->travelService->getContract($command->contract_id);
        if ($this->unit->ID == $contract->unit_id && $command->state == "open") {
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
    
    protected function makeCommandForm($name){
        $contracts = $this->context->travelService->getAllContractsPairs($this->unit->ID);
        $form = new AppForm($this, $name);
        $form->addSelect("contract_id", "Smlouva", $contracts)
                ->setPrompt("Vyberte smlouvu")
                ->addRule(Form::FILLED, "Musíte vybrat smlouvu");
        $form->addText("purpose", "Účel cesty*")
                ->addRule(Form::FILLED, "Musíte vyplnit účel cesty.");
        $form->addText("passengers", "Spolucestující");
        $form->addText("vehicle", "Vozidlo a SPZ*")
                ->addRule(Form::FILLED, "Musíte vyplnit typ vozidla.");
        $form->addText("consumption", "Průměrná spotřeba dle t.p.*")
                ->addRule(Form::FILLED, "Musíte vyplnit průměrnou spotřebu.");
        $form->addText("fuel_price", "Cena paliva za 1l*")
                ->addRule(Form::FILLED, "Musíte vyplnit cenu paliva.");
        $form->addText("amortization", "Opotřebení*", NULL, 2)
                ->addRule(Form::FILLED, "Musíte vyplnit opotřebení.");
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
        $v['state'] = "open";
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
        $this->redirect("default");
    }

}
