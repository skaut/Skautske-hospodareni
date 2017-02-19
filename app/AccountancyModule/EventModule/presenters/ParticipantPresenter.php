<?php

namespace App\AccountancyModule\EventModule;

use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;

/**
 * @author Hána František <sinacek@gmail.com>
 * účastníci
 */
class ParticipantPresenter extends BasePresenter
{

    use \ParticipantTrait;

    //kontrola oprávnění
    protected $isAllowParticipantDetail;
    //    protected $isAllowParticipantAll;
    //protected $isAllowParticipantDays;
    protected $isAllowParticipant;

    public function __construct(MemberService $member, ExportService $export, ExcelService $excel, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $export;
        $this->excelService = $excel;
        $this->pdf = $pdf;
    }

    function startup()
    {
        $this->traitStartup();
        $this->isAllowRepayment = $this->template->isAllowRepayment = FALSE;
        $this->isAllowIsAccount = $this->template->isAllowIsAccount = FALSE;

        $ev_state = $this->event->ID_EventGeneralState == "draft" ? TRUE : FALSE;
        $this->isAllowParticipantDetail = $this->template->isAllowParticipantDetail = array_key_exists("EV_ParticipantGeneral_DETAIL", $this->availableActions);
        //        $this->isAllowParticipantAll    = $this->template->isAllowParticipantAll = array_key_exists("EV_ParticipantGeneral_ALL_EventGeneral", $this->availableActions);
        //$this->isAllowParticipantDays = $this->template->isAllowParticipantDays = array_key_exists("EV_EventGeneral_UPDATE_Days", $this->availableActions);
        $this->isAllowParticipantDelete = $this->template->isAllowParticipantDelete = $ev_state && array_key_exists("EV_ParticipantGeneral_DELETE_EventGeneral", $this->availableActions);
        $this->isAllowParticipantInsert = $this->template->isAllowParticipantInsert = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->isAllowParticipantUpdate = $this->template->isAllowParticipantUpdate = $this->template->isAllowParticipantUpdateLocal = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
    }

    /**
     *
     * @param type $aid
     * @param type $uid
     * @param bool $dp - disabled person
     * @throws \Skautis\Wsdl\WsdlException
     */
    function renderDefault($aid, $uid = NULL, $dp = FALSE, $sort = NULL, $regNums = FALSE)
    {
        if (!$this->isAllowed("EV_ParticipantGeneral_ALL_EventGeneral")) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky akce", "danger");
            $this->redirect("Event:");
        }

        $this->traitDefault($dp, $sort, $regNums);

        //        $this->template->accessDeleteParticipant = $this->isAllowed("EV_ParticipantGeneral_DELETE_EventGeneral");
        //        $this->template->accessUpdateParticipant = $this->isAllowed("EV_ParticipantGeneral_UPDATE_EventGeneral");
        //        $this->template->accessInsertParticipant = $this->isAllowed("EV_ParticipantGeneral_INSERT_EventGeneral");
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionEditField($aid, $id, $field, $value)
    {
        if (!$this->isAllowParticipantUpdate) {
            $this->flashMessage("Nemáte oprávnění měnit účastníkův jejich údaje.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $oldData = $this->eventService->participants->get($id);
        if ($field == "days") {
            $arr = [
                "payment" => key_exists("payment", $oldData) ? $oldData['payment'] : 0,
                "days" => $value,
            ];
            $this->eventService->participants->update($id, $arr);
        } else if ($field == "payment") {
            $arr = [
                "payment" => $value,
                "days" => key_exists("days", $oldData) ? $oldData['days'] : NULL,
            ];
            $this->eventService->participants->update($id, $arr);
        }
        $this->payload->message = 'Success';
        $this->terminate();
    }

}
