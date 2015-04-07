<?php

namespace App\AccountancyModule\UnitAccountModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    /**
     *
     * @var \Model\MemberService
     */
    protected $memberService;

    /**
     * @var \Model\ExportService
     */
    protected $exportService;

    /**
     * @var \Model\ExcelService
     */
    protected $excelService;

    public function __construct(\Model\MemberService $member, \Model\ExportService $es, \Model\ExcelService $exs) {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $es;
        $this->excelService = $exs;
    }

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->getService("unitAccountService");

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

    public function renderDefault($aid, $pid = NULL, $disablePersons = FALSE) {
        if ($pid !== NULL) {
            $this->isChitEditable($pid, $this->entityService);
            $form = $this['cashbookForm'];
            $chit = $this->entityService->chits->get($pid);
            $form['category']->setItems($this->entityService->chits->getCategoriesPairs($chit->ctype, $this->aid));
            $form->setDefaults(array(
                "pid" => $pid,
                "date" => $chit->date->format("j. n. Y"),
                "recipient" => $chit->recipient,
                "purpose" => $chit->purpose,
                "price" => $chit->priceText,
                "type" => $chit->ctype,
                "category" => $chit->category,
            ));
        }

        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = $disablePersons ? array() : array_values($this->memberService->getCombobox(FALSE, 15));
        $this->template->list = $this->entityService->chits->getAll($aid);
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionExportExcel($aid) {
        $event = \Nette\Utils\ArrayHash::from(array("ID"=>$aid, "prefix"=>"", "DisplayName"=>"jednotka"));
        $this->excelService->getCashbook($this->entityService, $event);
        $this->terminate();
    }

}
