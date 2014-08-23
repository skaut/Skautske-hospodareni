<?php

namespace App\AccountancyModule\TravelModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ContractPresenter extends BasePresenter {

    function startup() {
        parent::startup();
//        $contractId = $this->getParameter("contractId", NULL);
//        $this->template->contract = $contract = $this->context->travelService->getContract($contractId);
//        $this->template->isEditable = $this->isEditable = ($contractId === NULL || $this->unit->ID == $contract->unit_id) ? true : false;
//        if (!$this->isEditable) {
//            $this->flashMessage("Neoprávněný přístup k cestovní smlouvě.", "danger");
//            $this->redirect("Default:");
//        }
    }

    protected function isContractAccessible($contractId) {
        return $this->context->travelService->isContractAccessible($contractId, $this->unit);
    }

    public function renderDefault() {
        $this->template->list = $this->context->travelService->getAllContracts($this->unit->ID);
    }

    public function renderDetail($id) {
        if (!$this->isContractAccessible($id)) {
            $this->flashMessage("Nemáte oprávnění k cestovnímu příkazu.", "danger");
            $this->redirect("default");
        }
        $this->template->contract = $contract = $this->context->travelService->getContract($id);
        $this->template->commands = $this->context->travelService->getAllCommandsByContract($this->unit->ID, $contract->id);
    }

    public function actionPrint($contractId) {
        $template = $this->template;
        $template->contract = $contract = $this->context->travelService->getContract($contractId);
        $template->unit = $this->context->unitService->getDetail($contract->unit_id);

        switch ($contract->template) {
            case 1:
                $templateName = 'ex.contract.old.latte';
                break;
            case 2:
                $templateName = 'ex.contract.noz.latte';
                break;
            default:
                throw new \Exception("Neznámá šablona pro " . $contract->template);
        }
        $template->setFile(dirname(__FILE__) . '/../templates/Contract/' . $templateName);
        $this->context->unitService->makePdf($template, "Smlouva-o-proplaceni-cestovnich-nahrad.pdf");
    }

    /**
     * formular na zalozeni nove smlouvy
     * @param type $name
     * @return \Form 
     */
    function createComponentFormCreateContract($name) {
        $form = new Form($this, $name);
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
                        ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateContractSubmitted(Form $form) {
        $v = $form->getValues();
        $v['end'] = isset($v['end']) ? $v['end'] : NULL;
        $v->unit_id = $this->unit->ID;
        if ($this->context->travelService->addContract($v)) {
            $this->flashMessage("Smlouva byla založena.");
        } else {
            $this->flashMessage("Smlouvu se nepodazřilo založit.", "danger");
        }
        $this->redirect("this");
    }

}
