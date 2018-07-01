<?php

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp;
use Model\Event\Commands\Camp\ActivateAutocomputedParticipants;
use Model\Event\SkautisCampId;
use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;

class ParticipantPresenter extends BasePresenter
{

    use \ParticipantTrait;

    const RULE_PARTICIPANTS_DETAIL = "EV_ParticipantCamp_DETAIL";
    const RULE_PARTICIPANTS_UPDATE_COST = "EV_ParticipantCamp_UPDATE_EventCamp_Note";
    const RULE_PARTICIPANTS_UPDATE_ADULT = "EV_EventCamp_UPDATE_Adult"; //Nastavit, zda se počty tábořících počítají automaticky

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
        $this->isAllowRepayment = TRUE;
        $this->isAllowIsAccount = TRUE;

        $this->isAllowParticipantInsert = $this->authorizator->isAllowed(Camp::ADD_PARTICIPANT, $this->aid);
        $this->isAllowParticipantDelete = $this->authorizator->isAllowed(Camp::REMOVE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->authorizator->isAllowed(Camp::UPDATE_PARTICIPANT, $this->aid);

        $this->template->setParameters([
            'isAllowParticipantInsert' => $this->isAllowParticipantInsert,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
        ]);
    }

    public function renderDefault(int $aid, $uid = NULL, $dp = FALSE, $sort = NULL, $regNums = FALSE) : void
    {
        $authorizator = $this->authorizator;

        if ( ! $authorizator->isAllowed(Camp::ACCESS_PARTICIPANTS, $aid)) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky", "danger");
            $this->redirect("Default:");
        }

        $this->traitDefault($dp, $sort, $regNums);

        $isAutocomputed = $this->event->IsRealAutoComputed;

        $this->template->setParameters([
            'isAllowParticipantDetail' => $authorizator->isAllowed(Camp::ACCESS_PARTICIPANT_DETAIL, $aid),
            'isAllowParticipantUpdateLocal' => $this->isAllowParticipantDelete,
            'missingAvailableAutoComputed' => ! $isAutocomputed && $authorizator->isAllowed(Camp::SET_AUTOMATIC_PARTICIPANTS_CALCULATION, $aid),
        ]);

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
        $data = ["actionId" => $aid];
        $sisdata = $this->eventService->participants->get($id);
        switch ($field) {
            case "days":
            case "payment":
            case "repayment":
            case "isAccount":
                $data[$field] = $value;
                break;
            default:
                $this->payload->message = 'Error';
                $this->sendPayload();
                break;
        }
        $this->eventService->participants->update($sisdata['ID'], $data);

        $this->payload->message = 'Success';
        $this->sendPayload();
    }

    public function handleActivateAutocomputedParticipants(int $aid) : void
    {
        $this->commandBus->handle(new ActivateAutocomputedParticipants(new SkautisCampId($aid)));
        $this->flashMessage("Byl aktivován automatický výpočet seznamu osobodnů.");
        $this->redirect("this");
    }

}
