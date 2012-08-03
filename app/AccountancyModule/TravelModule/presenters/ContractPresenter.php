<?php

/**
 * @author sinacek
 */
class Accountancy_Travel_ContractPresenter extends Accountancy_Travel_BasePresenter {

    
    function startup() {
        parent::startup();
        $contractId = $this->getParameter("contractId", NULL);
        $this->template->contract = $contract = $this->context->travelService->getContract($contractId);
        $this->template->isEditable = $this->isEditable = ($contractId === NULL || $this->unit->ID == $contract->unit_id) ? true : false;
        if (!$this->isEditable) {
            $this->flashMessage("Neoprávněný přístup k cestovní smlouvě.", "danger");
            $this->redirect("Default:");
        }
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllContracts($this->unit->ID);
    }
    public function renderDetail($contractId) {
        $this->template->contract = $contract = $this->context->travelService->getContract($contractId);
        $this->template->commands = $this->context->travelService->getAllCommands($this->unit->ID, $contract->id);
    }

    public function actionPrint($contractId) {
        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Contract/ex.contract.latte');
        $template->contract = $contract = $this->context->travelService->getContract($contractId);
        $template->unit = $this->context->unitService->getDetail($contract->unit_id);
        $this->context->unitService->makePdf($template, "Smlouva-o-proplaceni-cestovnich-nazhrad.pdf");
    }

    /**
     * formular na zalozeni nove smlouvy
     * @param type $name
     * @return \AppForm 
     */
    function createComponentFormCreateContract($name) {
        $form = new AppForm($this, $name);
        $form->addText("unit_person", "Zástupce jednotky*")
                ->addRule(Form::FILLED, "Musíte vyplnit zátupce jednotky.");
        $form->addText("driver_name", "Jméno a příjmení řidiče*")
                ->addRule(Form::FILLED, "Musíte vyplnit jméno řidiče.");
        $form->addText("driver_address", "Bydliště řidiče*")
                ->addRule(Form::FILLED, "Musíte vyplnit bydliště řidiče.");
        $form->addDatePicker("driver_birthday", "Datum narození řidiče*")
                ->addRule(Form::FILLED, "Musíte vyplnit datum narození řidiče.");
        $form->addText("driver_contact", "Telefon na řidiče (9cifer)*")
                ->addRule(Form::FILLED, "Musíte vyplnit telefon na řidiče.")
                ->addRule(Form::NUMERIC, "Telefon musí být číslo.");
        $form->addDatePicker("start", "Platnost od*")
                ->setDefaultValue(date("j. n. Y"))
                ->addRule(Form::FILLED, "Musíte vyplnit začátek platnosti.");

        $form->addSubmit('send', 'Založit smlouvu')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateContractSubmitted(AppForm $form) {
        $v = $form->getValues();
        $v['unit_id'] = $this->unit->ID;
        if($this->context->travelService->addContract($v))
            $this->flashMessage("Smlouva byla založena.");
        else 
            $this->flashMessage("Smlouvu se nepodazřilo založit.", "danger");
        $this->redirect("this");
    }

}
