<?php

namespace App\AccountancyModule\UnitAccountModule;

use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter
{

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

    public function __construct(MemberService $member, ExportService $es, ExcelService $exs, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $es;
        $this->excelService = $exs;
        $this->pdf = $pdf;
    }

    function startup()
    {
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

    public function actionDefault($aid, $pid = NULL, $dp = FALSE)
    {
        $items = $this['cashbookForm']['category']->getItems();
        unset($items[7]);//remove prevod do strediskove pokladny
        $this['cashbookForm']['category']->setItems($items);
    }

    public function renderDefault($aid, $pid = NULL, $dp = FALSE)
    {
        if ($pid !== NULL) {
            $this->isChitEditable($pid, $this->entityService);
            $form = $this['cashbookForm'];
            $chit = $this->entityService->chits->get($pid);
            $form['category']->setItems($this->entityService->chits->getCategoriesPairs($chit->ctype, $this->aid));
            $form->setDefaults([
                "pid" => $pid,
                "date" => $chit->date->format("j. n. Y"),
                "num" => $chit->num,
                "recipient" => $chit->recipient,
                "purpose" => $chit->purpose,
                "price" => $chit->priceText,
                "type" => $chit->ctype,
                "category" => $chit->category,
            ]);
        }

        $this->template->isInMinus = FALSE; //$this->context->unitAccountService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = $dp ? [] : array_values($this->memberService->getCombobox(FALSE, 15));
        $this->template->list = $this->entityService->chits->getAll($aid);
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionExportExcel($aid)
    {
        $event = \Nette\Utils\ArrayHash::from(["ID" => $aid, "prefix" => "", "DisplayName" => "jednotka"]);
        $this->excelService->getCashbook($this->entityService, $event);
        $this->terminate();
    }

}
