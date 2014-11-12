<?php

namespace App\AccountancyModule\UnitAccountModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "error");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->unitAccountService;

        /**
         * $object potřebuje šablona cashbook/table.latte
         */
        $this->template->object = $this->context->unitAccountService->event->get($this->aid);
        if (!$this->isReadable) {
            $this->flashMessage("Nemáš oprávnění číst data jednotky", "error");
            $this->redirect("Default:");
        }
        $this->template->unitPairs = $this->context->unitService->getReadUnits($this->user);
    }

    function renderDefault($aid, $disablePersons = FALSE) {
        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = $disablePersons ? array() : $this->context->memberService->getAC();
        $this->template->list = $this->context->unitAccountService->chits->getAll($aid);
//        if ($this->isAjax()) {
//            $this->invalidateControl("contentSnip");
//        }
    }

}
