<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author Hána František
 * účastníci
 */
class ParticipantPresenter extends BasePresenter {

    const RULE_PARTICIPANTS_DELETE = "EV_ParticipantCamp_DELETE";
    const RULE_PARTICIPANTS_DETAIL = "EV_ParticipantCamp_DETAIL";
    const RULE_PARTICIPANTS_UPDATE = "EV_ParticipantCamp_UPDATE_EventCamp";//Upravit tabořícího
    const RULE_PARTICIPANTS_UPDATE_ADULT = "EV_EventCamp_UPDATE_Adult";//Nastavit, zda se počty tábořících počítají automaticky
    const RULE_PARTICIPANTS_INSERT = "EV_ParticipantCamp_INSERT_EventCamp";

    protected $isAllowRepayment;
    protected $isAllowIsAccount;

    /**
     * číslo aktuální jendotky
     * @var int
     */
    protected $uid;

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Nepovolený přístup", "danger");
            $this->redirect("Default:");
        }
        $this->uid = $this->getParameter("uid", NULL);
        $this->isAllowRepayment = $this->template->isAllowRepayment = TRUE;
        $this->isAllowIsAccount = $this->template->isAllowIsAccount = TRUE;
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    function renderDefault($aid, $uid = NULL) {
        if (!$this->isAllowed("EV_ParticipantCamp_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky", "danger");
            $this->redirect("Default:");
        }

        $participants = $this->context->campService->participants->getAllWithDetails($this->aid);
        $unit = $this->context->unitService->getDetail($this->uid);
        $list = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        usort($participants, function ($a, $b) {
            return strcasecmp($a->Person, $b->Person);
        });
        natcasesort($list);

        $this->template->uparrent = $this->context->unitService->getParrent($unit->ID);
        $this->template->unit = $unit;
        $this->template->uchildrens = $this->context->unitService->getChild($unit->ID);
        $this->template->list = $list;
        $this->template->participants = $participants;
        $this->template->isAllowParticipantDelete = $this->isAllowed(self::RULE_PARTICIPANTS_DELETE);
        $this->template->isAllowParticipantDetail = $this->isAllowed(self::RULE_PARTICIPANTS_DETAIL);
        $this->template->isAllowParticipantUpdate = $this->isAllowed(self::RULE_PARTICIPANTS_UPDATE);
        $this->template->isAllowParticipantInsert = $this->isAllowed(self::RULE_PARTICIPANTS_INSERT);
        $this->template->missingAvailableAutoComputed = !$this->event->IsRealAutoComputed && $this->isAllowed(self::RULE_PARTICIPANTS_UPDATE_ADULT);
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionEditField($aid, $id, $field, $value) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_DETAIL)) {
            $this->flashMessage("Nemáte oprávnění měnit účastníkův jejich údaje.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }

        $data = (array)$this->context->campService->participants->get($id);
        $participantId = $data['participantId'];
        unset($data['participantId']);
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
        $this->context->campService->participants->update($participantId, $data);

        $this->payload->message = 'Success';
//        $this->sendResponse(new \Nette\Http\Response());
        $this->terminate();
    }

//    /**
//     * 
//     * @param type $aid - actionId
//     * @param type $pid - participantId
//     * @param type $dd - default days
//     */
//    public function actionEdit($aid, $pid, $dd = NULL) {
//        $form = $this['formEditParticipant'];
//        $data = $this->context->campService->participants->get($pid);
//
//        $form->setDefaults(array(
//            "days" => isset($dd) ? $dd : $data['days'],
//            "payment" => isset($data['payment']) ? $data['payment'] : "",
//            "repayment" => isset($data['repayment']) ? $data['repayment'] : "",
//            "isAccount" => isset($data['isAccount']) ? $data['isAccount'] : "N",
//            "user" => $pid,
//        ));
//    }

    public function renderExport($aid) {
        $template = $this->context->exportService->getParticipants($aid, $this->context->campService, "camp");
//        echo $template;die();
        $this->context->campService->participants->makePdf($template, "seznam-ucastniku.pdf", true);
        $this->terminate();
    }

    public function renderExportExcel($aid) {
        $this->context->excelService->getParticipants($this->context->campService, $this->event, "camp");
        $this->terminate();
    }

    public function handleActivateAutocomputedParticipants($aid) {
        $this->context->campService->event->activateAutocomputedParticipants($aid);
        $this->flashMessage("Byl aktivován automatický výpočet seznamu osobodnů.");
        $this->redirect("this");
    }

    public function handleRemove($pid) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_DELETE)) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $this->context->campService->participants->removeParticipant($pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
//            $this->invalidateControl("flash");
        } else {
            $this->redirect('this');
        }
    }

    public function handleAdd($pid) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_INSERT)) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $this->context->campService->participants->add($this->aid, $pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('this');
        }
    }

    /**
     * mění stav jestli vypisovat pouze přímé členy
     */
    public function handleChangeDirectMemberOnly() {
        $this->setDirectMemberOnly(!$this->getDirectMemberOnly());
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
        } else {
            $this->redirect('this', array("aid" => $this->aid, "uid" => $this->uid));
        }
    }

//    public function createComponentFormEditParticipant($name) {
//        $form = new Form($this, $name);
//        $form->addText("days", "Dní");
//        $form->addText("payment", "Částka");
//        $form->addText("repayment", "Vratka");
//        $form->addRadioList("isAccount", "Na účet?", array("N" => "Ne", "Y" => "Ano"));
//        $form->addHidden("user");
//        $form->addSubmit('send', 'Upravit')
//                        ->setAttribute('class', 'btn btn-primary')
//                ->onClick[] = $this->{$name . 'Submitted'};
//        return $form;
//    }
//
//    public function formEditParticipantSubmitted(SubmitButton $button) {
//        if (!$this->isAllowed(self::RULE_PARTICIPANTS_UPDATE)) {
//            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
//            $this->redirect("Default:");
//        }
//
//        $values = $button->getForm()->getValues(TRUE);
//        $values['repayment'] = $values['repayment'] != "" ? $values['repayment'] : 0;
//        $values['actionId'] = $this->aid;
//        $this->context->campService->participants->update($values['user'], $values);
//
//        if ($this->isAjax()) {
//            $this->flashMessage("Účastník byl upraven.");
//            $this->invalidateControl("flash");
//            $this->invalidateControl("potencialParticipants");
//            $this->invalidateControl("participants");
//        } else {
//            $this->redirect('default', $this->aid);
//        }
//    }

    public function createComponentFormMassList($name) {
        $form = new Form($this, $name);
        $form->addSubmit('send')
                ->onClick[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassListSubmitted(SubmitButton $button) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_INSERT)) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }
        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massList[]') as $id) {
            $this->context->campService->participants->add($this->aid, $id);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    public function createComponentFormMassParticipants($name) {
        $form = new Form($this, $name);
        $form->addProtection();

        $editCon = $form->addContainer("edit");
        $editCon->addText("days", "Dní");
        $editCon->addText("payment", "Částka");
        $editCon->addText("repayment", "Vratka");
        $editCon->addRadioList("isAccount", "Na účet?", array("N" => "Ne", "Y" => "Ano"));
        $editCon->addCheckbox("daysc");
        $editCon->addCheckbox("paymentc");
        $editCon->addCheckbox("repaymentc");
        $editCon->addCheckbox("isAccountc"); //->setDefaultValue(TRUE);
        $editCon->addSubmit('send', 'Upravit')
                        ->setAttribute('class', 'btn btn-info btn-small')
                ->onClick[] = $this->massEditSubmitted;


        $form->addSubmit('send', 'Odebrat vybrané')
                ->onClick[] = $this->massRemoveSubmitted;
    }

    public function massEditSubmitted(SubmitButton $button) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_UPDATE)) {
            $this->flashMessage("Nemáte právo upravovat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $values = $button->getForm()->getValues();
        $data = array("actionId" => $this->aid);
        if ($values['edit']['daysc']) {
            $data['days'] = $values['edit']['days'];
        }
        if ($values['edit']['paymentc']) {
            $data['payment'] = $values['edit']['payment'];
        }
        if ($values['edit']['repaymentc']) {
            $data['repayment'] = $values['edit']['repayment'];
        }
        if ($values['edit']['isAccountc']) {
            $data['isAccount'] = $values['edit']['isAccount'];
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $this->context->campService->participants->update($id, $data);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    public function massRemoveSubmitted(SubmitButton $button) {
        if (!$this->isAllowed(self::RULE_PARTICIPANTS_DELETE)) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("Default:");
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $this->context->campService->participants->removeParticipant($id);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    /**
     * formulář na přidání nové osoby
     * @param string $name
     * @return Form
     */
    function createComponentFormAddParticipantNew($name) {
        $aid = $this->presenter->aid;
        $form = new Form($this, $name);
        $form->addText("firstName", "Jméno")
                ->addRule(Form::FILLED, "Musíš vyplnit křestní jméno.");
        $form->addText("lastName", "Příjmení")
                ->addRule(Form::FILLED, "Musíš vyplnit příjmení.");
        $form->addText("nick", "Přezdívka");
        $form->addText("birthday", "Dat. nar.");
        $form->addText("street", "Ulice");
        $form->addText("city", "Město");
        $form->addText("postcode", "PSČ");
        $form->addHidden("aid", $aid);
        $form->addSubmit('send', 'Založit účastníka')
                        ->setAttribute("class", "btn btn-primary")
                ->onClick[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formAddParticipantNewSubmitted(SubmitButton $button) {
        $this->editableOnly();
        $values = $button->getForm()->getValues();
        $aid = $values['aid'];
        $person = array(
            "firstName" => $values['firstName'],
            "lastName" => $values['lastName'],
            "nick" => $values['nick'],
            "Birthday" => date("c", strtotime($values['birthday'])),
            "street" => $values['street'],
            "city" => $values['city'],
            "postcode" => $values['postcode'],
        );
        $this->context->campService->participants->addNew($aid, $person);
        $this->redirect("this");
    }

    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

}
