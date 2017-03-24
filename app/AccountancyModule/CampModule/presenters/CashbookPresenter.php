<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->eventService;
        $this->template->isEditable = $this->isEditable = ($this->isEditable || $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCostBeforeEnd"));
        //        $this->template->isEditable = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
    }

    public function renderDefault($aid, $pid = NULL, $dp = FALSE) : void
    {
        if ($pid !== NULL) {
            $this->editChit($pid);
        }

        $this->template->isInMinus = $this->eventService->chits->eventIsInMinus($this->aid);

        $this->template->list = $this->eventService->chits->getAll($aid);
        $this->template->missingCategories = FALSE;
        $this->template->linkImportHPD = "#importHpd";

        $this->fillTemplateVariables();

        if (!$this->event->IsRealTotalCostAutoComputed) { //nabízí možnost aktivovat dopočítávání, pokud již není aktivní a je dostupná
            //$this->template->isAllowedUpdateRealTotalCost = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
            $this->template->missingCategories = TRUE; //boolean - nastavuje upozornění na chybějící dopočítávání kategorií
        }
        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

    public function handleActivateAutocomputedCashbook($aid) : void
    {
        try {
            $this->eventService->event->activateAutocomputedCashbook($aid);
            $this->flashMessage("Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.");
        } catch (\Skautis\Wsdl\PermissionException $e) {
            $this->flashMessage("Dopočítávání se nepodařilo aktivovat. Pro aktivaci musí být tábor alespoň ve stavu schváleno střediskem.", "danger");
        }
        $this->redirect("this");
    }

    /**
     * formulář na výběr příjmů k importu
     * @param type $name
     * @return Form
     */
    protected function createComponentFormImportHpd($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addRadioList("cat", "Kategorie:", ["child" => "Od dětí a roverů", "adult" => "Od dospělých"])
            ->addRule(Form::FILLED, "Musíte vyplnit kategorii.")
            ->setDefaultValue("child");
        $form->addRadioList("isAccount", "Placeno:", ["N" => "Hotově", "Y" => "Přes účet"])
            ->addRule(Form::FILLED, "Musíte vyplnit způsob platby.")
            ->setDefaultValue("N");
        $form->addHidden("aid", $this->aid);

        $form->addSubmit('send', 'Importovat')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(Form $form) : void {
            $this->formImportHpdSubmitted($form);
        };

        $form->setDefaults(['category' => 'un']);

        return $form;
    }

    private function formImportHpdSubmitted(Form $form) : void
    {
        $this->editableOnly();
        $values = $form->getValues();
        $data = ["date" => $this->eventService->event->get($values->aid)->StartDate,
            "recipient" => "",
            "purpose" => "úč. příspěvky " . ($values->isAccount == "Y" ? "- účet" : "- hotovost"),
            "price" => $this->eventService->participants->getCampTotalPayment($values->aid, $values->cat, $values->isAccount),
            "category" => $this->eventService->chits->getParticipantIncomeCategory($values->aid, $values->cat)];

        if ($this->eventService->chits->add($values->aid, $data)) {
            $this->flashMessage("HPD byl importován");
        } else {
            $this->flashMessage("HPD se nepodařilo importovat", "danger");
        }
        $this->redirect("default", ["aid" => $values->aid]);
    }

}
