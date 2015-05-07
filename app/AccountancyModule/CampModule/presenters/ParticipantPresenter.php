<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author Hána František <sinacek@gmail.com> 
 */
class ParticipantPresenter extends BasePresenter {

    use \ParticipantTrait;

    const RULE_PARTICIPANTS_DELETE = "EV_ParticipantCamp_DELETE";
    const RULE_PARTICIPANTS_DETAIL = "EV_ParticipantCamp_DETAIL";
    const RULE_PARTICIPANTS_UPDATE = "EV_ParticipantCamp_UPDATE_EventCamp"; //Upravit tabořícího
    const RULE_PARTICIPANTS_UPDATE_COST = "EV_ParticipantCamp_UPDATE_EventCamp_Note";
    const RULE_PARTICIPANTS_UPDATE_ADULT = "EV_EventCamp_UPDATE_Adult"; //Nastavit, zda se počty tábořících počítají automaticky
    const RULE_PARTICIPANTS_INSERT = "EV_ParticipantCamp_INSERT_EventCamp";

    //const RULE_PARTICIPANTS_INSERT_MULTI = "OU_Person_ALL_EventCampMulti";

    public function __construct(\Model\MemberService $member, \Model\ExportService $export, \Model\ExcelService $excel) {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $export;
        $this->excelService = $excel;
    }

    function startup() {
        $this->traitStartup();
        $this->isAllowRepayment = $this->template->isAllowRepayment = TRUE;
        $this->isAllowIsAccount = $this->template->isAllowIsAccount = TRUE;

        $this->isAllowParticipantInsert = $this->template->isAllowParticipantInsert = $this->isAllowed(self::RULE_PARTICIPANTS_INSERT);
        $this->isAllowParticipantDelete = $this->template->isAllowParticipantDelete = $this->isAllowed(self::RULE_PARTICIPANTS_DELETE);
        $this->isAllowParticipantUpdate = $this->template->isAllowParticipantUpdate = $this->isAllowed(self::RULE_PARTICIPANTS_UPDATE);
    }

    function renderDefault($aid, $uid = NULL, $dp = FALSE, $sort = NULL, $regNums = FALSE) {
        if (!$this->isAllowed("EV_ParticipantCamp_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky", "danger");
            $this->redirect("Default:");
        }

        $this->traitDefault($dp, $sort, $regNums);


//        if ($this->isAllowParticipantInsert) {
//            $this->template->list = $this->campService->participants->getPotencialCampParticipants($aid);
//        }

        $this->template->isAllowParticipantDetail = $this->isAllowed(self::RULE_PARTICIPANTS_DETAIL);
        $this->template->isAllowParticipantUpdateLocal = $this->isAllowParticipantDelete;
        $this->template->missingAvailableAutoComputed = !$this->event->IsRealAutoComputed && $this->isAllowed(self::RULE_PARTICIPANTS_UPDATE_ADULT);

        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionEditField($aid, $id, $field, $value) {
        if (!$this->isAllowParticipantUpdate) {
            $this->flashMessage("Nemáte oprávnění měnit účastníkův jejich údaje.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $data = array("actionId" => $aid);
        $sisdata = (array) $this->eventService->participants->get($id);
        switch ($field) {
            case "days":
            case "payment":
            case "repayment":
            case "isAccount":
                $data[$field] = $value;
                break;
            default:
                $this->payload->message = 'Error';
                $this->terminate();
                break;
        }
        $this->eventService->participants->update($sisdata['ID'], $data);

        $this->payload->message = 'Success';
        $this->terminate();
    }

    public function handleActivateAutocomputedParticipants($aid) {
        $this->eventService->event->activateAutocomputedParticipants($aid);
        $this->flashMessage("Byl aktivován automatický výpočet seznamu osobodnů.");
        $this->redirect("this");
    }

}
