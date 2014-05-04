<?php

namespace App\AccountancyModule\EventModule;

use Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

/**
 * @author Hána František
 * účastníci
 */
class ParticipantPresenter extends BasePresenter {

    /**
     * číslo aktuální jendotky
     * @var int
     */
    protected $uid;
    //kontrola oprávnění
    protected $isAllowParticipantDetail;
//    protected $isAllowParticipantAll;
    protected $isAllowParticipantDelete;
    //protected $isAllowParticipantDays;
    protected $isAllowParticipant;
    protected $isAllowParticipantInsert;
    protected $isAllowParticipantUpdate;
    protected $isAllowRepayment;
    protected $isAllowIsAccount;

    function startup() {
        parent::startup();

        if (!$this->aid) {
            $this->flashMessage("Nepovolený přístup", "danger");
            $this->redirect("Default:");
        }
        $this->uid = $this->getParameter("uid", NULL);
        $this->isAllowParticipantDetail = $this->template->isAllowParticipantDetail = array_key_exists("EV_ParticipantGeneral_DETAIL", $this->availableActions);
//        $this->isAllowParticipantAll    = $this->template->isAllowParticipantAll = array_key_exists("EV_ParticipantGeneral_ALL_EventGeneral", $this->availableActions);
        //$this->isAllowParticipantDays = $this->template->isAllowParticipantDays = array_key_exists("EV_EventGeneral_UPDATE_Days", $this->availableActions);
        $this->isAllowParticipantDelete = $this->template->isAllowParticipantDelete = array_key_exists("EV_ParticipantGeneral_DELETE_EventGeneral", $this->availableActions);
        $this->isAllowParticipantInsert = $this->template->isAllowParticipantInsert = array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->isAllowParticipantUpdate = $this->template->isAllowParticipantUpdate = array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->isAllowRepayment = $this->template->isAllowRepayment = FALSE;
        $this->isAllowIsAccount = $this->template->isAllowIsAccount = FALSE;
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    function renderDefault($aid, $uid = NULL) {
        if (!$this->isAllowed("EV_ParticipantGeneral_ALL_EventGeneral")) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky akce", "danger");
            $this->redirect("Event:");
        }

        $participants = $this->context->eventService->participants->getAll($this->aid, $cache = FALSE);
        $list = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

//        usort($participants, function($a, $b) {/* setrizeni podle abecedy */
//                    return strcasecmp($a->Person, $b->Person);
//                });
//        natcasesort($list);

        $this->template->participants = $participants;
        $this->template->list = $list;
        $this->template->unit = $unit = $this->context->unitService->getDetail($this->uid);
        $this->template->uparrent = $this->context->unitService->getParrent($unit->ID);
        $this->template->uchildrens = $this->context->unitService->getChild($unit->ID);

//        $this->template->accessDeleteParticipant = $this->isAllowed("EV_ParticipantGeneral_DELETE_EventGeneral");
//        $this->template->accessUpdateParticipant = $this->isAllowed("EV_ParticipantGeneral_UPDATE_EventGeneral");
//        $this->template->accessInsertParticipant = $this->isAllowed("EV_ParticipantGeneral_INSERT_EventGeneral");
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
        
        $oldData = $this->context->eventService->participants->get($id);
        if ($field == "days") {
            $arr = array(
                "payment" => @$oldData['payment'],
                "days" => $value,
            );
            $this->context->eventService->participants->update($id, $arr);
        } else if ($field == "payment") {
            $arr = array(
                "payment" => $value,
                "days" => @$oldData['days'],
            );
            $this->context->eventService->participants->update($id, $arr);
        }
        $this->payload->message = 'Success';
//        $this->sendResponse(new \Nette\Http\Response());
        $this->terminate();
    }

    public function renderExport($aid) {
        $template = $this->context->exportService->getParticipants($aid, $this->context->eventService);
        $this->context->eventService->participants->makePdf($template, "seznam-ucastniku.pdf");
        $this->terminate();
    }

    public function renderExportExcel($aid) {
        $this->context->excelService->getParticipants($this->context->eventService, $this->event);
        $this->terminate();
    }

    public function handleRemove($pid) {
        if (!$this->isAllowParticipantDelete) {
            $this->flashMessage("Nemáte oprávnění odebírat účastníky.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $this->context->eventService->participants->removeParticipant($pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
//            $this->invalidateControl("flash");
        } else {
            $this->redirect('this');
        }
    }

    public function handleAdd($pid) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte oprávnění přidávat účastníky.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $this->context->eventService->participants->add($this->aid, $pid);
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

    public function createComponentFormMassList($name) {
        $form = new Form($this, $name);
        $form->addSubmit('send')
                ->onClick[] = $this->{$name . 'Submitted'};
        return $form;
    }

    public function formMassListSubmitted(SubmitButton $button) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }
        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massList[]') as $id) {
            $this->context->eventService->participants->add($this->aid, $id);
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
        if (!$this->isAllowParticipantUpdate) {
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
            $this->context->eventService->participants->update($id, $data);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    public function massRemoveSubmitted(SubmitButton $button) {
        if (!$this->isAllowParticipantDelete) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("Default:");
        }
        
        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $this->context->eventService->participants->removeParticipant($id);
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
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formAddParticipantNewSubmitted(Form $form) {
        if (!$this->isAllowParticipantInsert) {
            $this->flashMessage("Nemáte oprávnění přidávat účastníky.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
        $values = $form->getValues();
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
        $this->context->eventService->participants->addNew($aid, $person);
        $this->redirect("this");
    }

    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

}
