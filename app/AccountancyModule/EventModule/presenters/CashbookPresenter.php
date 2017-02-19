<?php

namespace App\AccountancyModule\EventModule;

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
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Event:");
        }
        $this->entityService = $this->eventService;

        $ev_state = $this->event->ID_EventGeneralState == "draft" ? TRUE : FALSE;
        $this->isEditable = $this->template->isEditable = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->template->missingCategories = FALSE;
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

        $this->template->isInMinus = $this->eventService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->autoCompleter = [];
        if (!$dp) {
            try {
                $this->template->autoCompleter = array_values($this->memberService->getCombobox(FALSE, 15));
            } catch (\Skautis\Wsdl\WsdlException $exc) {

            }
        }
        $this->template->list = $this->eventService->chits->getAll($aid);
        $this->template->linkImportHPD = $this->link("importHpd", ["aid" => $aid]);
        $this->template->object = $this->event;
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionImportHpd($aid)
    {
        $this->editableOnly();
        $totalPayment = $this->eventService->participants->getTotalPayment($this->aid);
        $func = $this->eventService->event->getFunctions($this->aid);
        $date = $this->eventService->event->get($aid)->StartDate;
        $hospodar = ($func[2]->ID_Person != NULL) ? $func[2]->Person : ""; //$func[0]->Person
        $category = $this->eventService->chits->getParticipantIncomeCategory();

        $values = ["date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category];
        $add = $this->eventService->chits->add($this->aid, $values);
        if ($add) {
            $this->flashMessage("Účastníci byli importováni");
        } else {
            $this->flashMessage("Účastníky se nepodařilo importovat", "danger");
        }
        $this->redirect("default", ["aid" => $aid]);
    }

}
