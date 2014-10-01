<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "error");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->campService;
        $this->template->isEditable = $this->isEditable = ($this->isEditable || $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCostBeforeEnd"));
//        $this->template->isEditable = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
    }

    function renderDefault($aid) {
        $this->template->isInMinus = $this->context->campService->chits->eventIsInMinus($this->aid);
        $this->template->autoCompleter = $this->context->memberService->getAC();
        $this->template->list = $this->context->campService->chits->getAll($aid);
        $this->template->missingCategories = false;
        $this->template->linkImportHPD = "#importHpd";
        $this->template->object = $this->event;
//        dump($this->camp);
        if (!$this->event->IsRealTotalCostAutoComputed) { //nabízí možnost aktivovat dopočítávání, pokud již není aktivní a je dostupná
            //$this->template->isAllowedUpdateRealTotalCost = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
            $this->template->missingCategories = true; //boolean - nastavuje upozornění na chybějící dopočítávání kategorií
            $this->template->skautISHttpPrefix = $this->context->skautIS->getHttpPrefix();
        }
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function handleActivateAutocomputedCashbook($aid) {
        $this->context->campService->event->activateAutocomputedCashbook($aid);
        $this->flashMessage("Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.");
        $this->redirect("this");
    }

    /**
     * formulář na výběr příjmů k importu
     * @param type $name
     * @return \Nette\Application\UI\Form
     */
    function createComponentFormImportHpd($name) {
        $form = new Form($this, $name);
        $form->addRadioList("cat", "Kategorie:", array("child" => "Od dětí a roverů", "adult" => "Od dospělých"))
                ->addRule(Form::FILLED, "Musíte vyplnit kategorii.")
                ->setDefaultValue("child");
        $form->addRadioList("isAccount", "Placeno:", array("N" => "Hotově", "Y" => "Přes účet"))
                ->addRule(Form::FILLED, "Musíte vyplnit způsob platby.")
                ->setDefaultValue("N");
        $form->addHidden("aid", $this->aid);

        $form->addSubmit('send', 'Importovat')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        $form->setDefaults(array('category' => 'un'));
        return $form;
    }

    function formImportHpdSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();
        $data = array("date" => $this->context->campService->event->get($values->aid)->StartDate,
            "recipient" => "",
            "purpose" => "úč. příspěvky " . ($values->isAccount == "Y" ? "- účet" : "- hotovost"),
            "price" => $this->context->campService->participants->getCampTotalPayment($values->aid, $values->cat, $values->isAccount),
            "category" => $this->context->campService->chits->getParticipantIncomeCategory($values->aid, $values->cat));

        if ($this->context->campService->chits->add($values->aid, $data)) {
            $this->flashMessage("HPD byl importován");
        } else {
            $this->flashMessage("HPD se nepodařilo importovat", "fail");
        }
        $this->redirect("default", array("aid" => $values->aid));
    }

}
