<?php

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event;
use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;

class ParticipantPresenter extends BasePresenter
{

    use \ParticipantTrait;

    //kontrola oprávnění
    protected $isAllowParticipantDetail;

    protected $isAllowParticipant;

    public function __construct(MemberService $member, ExportService $export, ExcelService $excel, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $export;
        $this->excelService = $excel;
        $this->pdf = $pdf;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->traitStartup();
        $this->isAllowRepayment = FALSE;
        $this->isAllowIsAccount = FALSE;

        $isDraft = $this->event->ID_EventGeneralState === 'draft';
        $authorizator = $this->authorizator;

        $this->isAllowParticipantDetail = $authorizator->isAllowed(Event::ACCESS_DETAIL, $this->aid);
        $this->isAllowParticipantDelete = $isDraft && $authorizator->isAllowed(Event::REMOVE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantInsert = $isDraft && $authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->isAllowParticipantInsert;

        $this->template->setParameters([
            'isAllowParticipantDetail' => $this->isAllowParticipantDetail,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
            'isAllowParticipantInsert' => $this->isAllowParticipantInsert,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowParticipantUpdateLocal' => $this->isAllowParticipantUpdate,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
        ]);
    }

    /**
     *
     * @param bool $dp - disabled person
     * @throws \Skautis\Wsdl\WsdlException
     */
    public function renderDefault(?int $aid, ?int $uid = NULL, bool $dp = FALSE, $sort = NULL, $regNums = FALSE) : void
    {
        if ( ! $this->authorizator->isAllowed(Event::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky akce", "danger");
            $this->redirect("Event:");
        }

        $this->traitDefault($dp, $sort, $regNums);

        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

    public function actionEditField($aid, $id, $field, $value) : void
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
        $this->sendPayload();
    }

}
