<?php

namespace App\AccountancyModule\UnitAccountModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    protected $object;

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "error");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->unitAccountService;

        $this->template->object = $this->object = $this->context->unitAccountService->event->get($this->context->unitService->getDetail()->ID);
        if (!$this->isReadable) {
            $this->flashMessage("Nemáš oprávnění číst data jednotky", "error");
            $this->redirect("Default:");
        }
    }

    function renderDefault($aid) {
        //@todo: opravit aby to kontrolovalo isInMinus
        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = $this->context->memberService->getAC();
        $this->template->list = $this->context->unitAccountService->chits->getAll($aid);
//        if ($this->isAjax()) {
//            $this->invalidateControl("contentSnip");
//        }
    }

}
