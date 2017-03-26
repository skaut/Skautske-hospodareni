<?php

namespace App\AccountancyModule\TravelModule;

use Model\Services\PdfRenderer;
use Nette\Application\UI\Form;
use Model\TravelService;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ContractPresenter extends BasePresenter
{

    /** @var TravelService */
    private $travelService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(TravelService $travelService, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->travelService = $travelService;
        $this->pdf = $pdf;
    }

    protected function isContractAccessible($contractId) : bool
    {
        return $this->travelService->isContractAccessible($contractId, $this->unit);
    }

    public function renderDefault() : void
    {
        $this->template->list = $this->travelService->getAllContracts($this->unit->ID);
    }

    public function renderDetail($id) : void
    {
        if (!$this->isContractAccessible($id)) {
            $this->flashMessage("Nemáte oprávnění k cestovnímu příkazu.", "danger");
            $this->redirect("default");
        }
        $this->template->contract = $contract = $this->travelService->getContract($id);
        $this->template->commands = $this->travelService->getAllCommandsByContract($this->unit->ID, $contract->id);
    }

    public function actionPrint($contractId) : void
    {
        $template = $this->template;
        $template->contract = $contract = $this->travelService->getContract($contractId);
        $template->unit = $this->unitService->getDetail($contract->unit_id);

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

        $this->pdf->render($template, 'Smlouva-o-proplaceni-cestovnich-nahrad.pdf');
    }

    public function handleDelete($contractId) : void
    {
        $commands = $this->travelService->getAllCommandsByContract($this->unit->ID, $contractId);
        if (!empty($commands)) {
            $this->flashMessage("Nelze smazat smlouvu s navázanými cestovními příkazy!", "danger");
            $this->redirect("this");
        }
        $this->travelService->deleteContract($contractId);
        $this->flashMessage("Smlouva byla smazána", "success");
        $this->redirect("default");
    }

    protected function createComponentFormCreateContract(string $name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addText("driver_name", "Jméno a příjmení řidiče*")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte vyplnit jméno řidiče.");
        $form->addText("driver_address", "Bydliště řidiče*")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte vyplnit bydliště řidiče.");
        $form->addDatePicker("driver_birthday", "Datum narození řidiče*")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte vyplnit datum narození řidiče.");
        $form->addText("driver_contact", "Telefon na řidiče (9cifer)*")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Musíte vyplnit telefon na řidiče.")
            ->addRule(Form::NUMERIC, "Telefon musí být číslo.");

        $form->addText("unit_person", "Zástupce jednotky")
            ->setAttribute("class", "form-control");
        $form->addDatePicker("start", "Platnost od")
            ->setAttribute("class", "form-control");

        $form->addSubmit('send', 'Založit smlouvu')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(Form $form) : void {
            $this->formCreateContractSubmitted($form);
        };

        return $form;
    }

    private function formCreateContractSubmitted(Form $form) : void
    {
        $v = $form->getValues();
        $v['end'] = isset($v['end']) ? $v['end'] : NULL;
        $v->unit_id = $this->unit->ID;
        if ($this->travelService->addContract($v)) {
            $this->flashMessage("Smlouva byla založena.");
        } else {
            $this->flashMessage("Smlouvu se nepodazřilo založit.", "danger");
        }
        $this->redirect("this");
    }

}
