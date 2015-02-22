<?php

namespace App\AccountancyModule\UnitAccountModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    protected $memberService;
    
    public function __construct(\Model\MemberService $ms) {
        parent::__construct();
        $this->memberService = $ms;
    }
            
    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->unitAccountService;

        /**
         * $object potřebuje šablona cashbook/table.latte
         */
        $this->template->object = $this->entityService->event->get($this->aid);
        if (!$this->isReadable) {
            $this->flashMessage("Nemáš oprávnění číst data jednotky", "danger");
            $this->redirect("Default:");
        }
        $this->template->unitPairs = $this->unitService->getReadUnits($this->user);
    }

    function renderDefault($aid, $disablePersons = FALSE) {
        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = $disablePersons ? array() : $this->memberService->getAC();
        $this->template->list = $this->entityService->chits->getAll($aid);
    }

}
