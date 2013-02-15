<?php

namespace AccountancyModule\CampModule;

use Nette\Application\UI\Form;

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

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Nepovolený přístup", "danger");
            $this->redirect("Default:");
        }
        $this->uid = $this->getParameter("uid", NULL);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    function renderDefault($aid, $uid = NULL) {
        if (!array_key_exists("EV_ParticipantCamp_ALL_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky", "danger");
            $this->redirect("Default:");
        }

        $participants = $this->context->campService->participants->getAllWithDetails($this->aid);
        $unit = $this->context->unitService->getDetail($this->uid);
        $list = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        //setrizeni podle abecedy
        function cmpParticipants($a, $b) {
            return strcasecmp($a->Person, $b->Person);
        }

        usort($participants, "cmpParticipants");
        natcasesort($list);

        $this->template->uparrent = $this->context->unitService->getParrent($unit->ID);
        $this->template->unit = $unit;
        $this->template->uchildrens = $this->context->unitService->getChild($unit->ID);
        $this->template->list = $list;
        $this->template->participants = $participants;
        $this->template->accessDeleteParticipant = array_key_exists("EV_ParticipantCamp_DELETE", $this->availableActions);
        $this->template->accessUpdateParticipant = array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions);
        $this->template->accessInsertParticipant = array_key_exists("EV_ParticipantCamp_INSERT_EventCamp", $this->availableActions);
    }

    /**
     * 
     * @param type $aid - actionId
     * @param type $pid - participantId
     * @param type $dd - default days
     */
    public function actionEdit($aid, $pid, $dd = NULL) {
        $form = $this['formEditParticipant'];
        $data = $this->context->campService->participants->get($pid);

        $form->setDefaults(array(
            "days" => isset($dd) ? $dd : $data['days'],
            "payment" => isset($data['payment']) ? $data['payment'] : "",
            "repayment" => isset($data['repayment']) ? $data['repayment'] : "",
            "isAccount" => isset($data['isAccount']) ? $data['isAccount'] : "N",
            "user" => $pid,
        ));
    }

    public function renderExport($aid) {
        $actionInfo = $this->context->campService->event->get($aid);
        $list = $this->context->campService->participants->getAllWithDetails($aid);
//        $list = $this->context->campService->participants->getAllDetail($aid, $participants);

        $template = $this->template;
        $template->info = $actionInfo;
        $template->list = $list;
        $template->setFile(dirname(__FILE__) . '/../templates/Participant/export.latte');
//        echo $template;die();
        $this->context->campService->participants->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_ucastnici.pdf", true);
        $this->terminate();
    }

    public function renderHpd($aid) {
        $actionInfo = $this->context->campService->event->get($aid);
        $list = $this->context->campService->participants->getAll($aid);
        $template = $this->template;
        $template->oficialName = $this->context->unitService->getOficialName($actionInfo->ID_Unit);
        $template->totalPayment = $this->context->campService->participants->getTotalPayment($aid);
        $template->list = $list;
        $template->setFile(dirname(__FILE__) . '/../templates/Participant/ex.hpd.latte');
        $this->context->campService->participants->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_hpd.pdf");
        $this->terminate();
    }

    public function handleRemove($pid) {
        if (!array_key_exists("EV_ParticipantCamp_DELETE", $this->availableActions)) {
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
        if (!array_key_exists("EV_ParticipantCamp_INSERT_EventCamp", $this->availableActions)) {
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

    public function createComponentFormEditParticipant($name) {
        $form = new Form($this, $name);
        $form->addText("days", "Dní");
        $form->addText("payment", "Částka");
        $form->addText("repayment", "Vratka");
        $form->addRadioList("isAccount", "Na účet?", array("N" => "Ne", "Y" => "Ano"));
        $form->addHidden("user");
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formEditParticipantSubmitted(Form $form) {
        if (!array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }

        $values = (array) $form->getValues();
        $values['actionId'] = $this->aid;
        $this->context->campService->participants->update($values['user'], $values);

        if ($this->isAjax()) {
            $this->flashMessage("Účastník byl upraven.");
            $this->invalidateControl("flash");
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('default', $this->aid);
        }
    }

    public function createComponentFormMassAdd($name) {
        $participants = $this->context->campService->participants->getAll($this->aid);
        $all = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        $form = new Form($this, $name);

        $group = $form->addContainer('all');
        foreach ($all as $id => $p) {
            $group->addCheckbox($id, $p);
        }

        $form->addSubmit('massAddSend', 'Přidat vybrané');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassAddSubmitted(Form $form) {
        if (!array_key_exists("EV_ParticipantCamp_INSERT_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }

        $values = $form->getValues();

        foreach ($values['all'] as $id => $bool) {
            if ($bool)
                $this->context->campService->participants->add($this->aid, $id);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid)); //TODO je to ok?
    }

    public function createComponentFormMassParticipants($name) {
        $participants = $this->context->campService->participants->getAll($this->aid);

        $form = new Form($this, $name);

        $group = $form->addContainer('ids');
        foreach ($participants as $id => $p) {
            $group->addCheckbox($p->ID, $p->Person);
        }

        $isChange = $form->addContainer('isChange');
        $isChange->addText("days", "Dní");
        $isChange->addText("payment", "Částka");
        $isChange->addText("repayment", "Vratka");
        $isChange->addRadioList("isAccount", "Na účet?", array("N" => "Ne", "Y" => "Ano"));
        $isChange->addCheckbox("daysc");
        $isChange->addCheckbox("paymentc");
        $isChange->addCheckbox("repaymentc");
        $isChange->addCheckbox("isAccountc"); //->setDefaultValue(TRUE);
        //tlačitko upravit vybrané
        $form->addSubmit('massEditSend', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-info btn-small");
        $form['massEditSend']->onClick[] = callback($this, 'massEditSubmitted');

        //tlačitko smazat vybrané
        $form->addSubmit('massRemoveSend', 'Odebrat vybrané')
                ->getControlPrototype()
                ->setOnclick("return confirm('Opravdu chcete odebrat vybrané účastníky?')");
        $form['massRemoveSend']->onClick[] = callback($this, 'massRemoveSubmitted');

//        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function massEditSubmitted(SubmitButton $button) {
        if (!array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo upravovat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $values = $button->getForm()->getValues();
        $data = array("actionId"=>$this->aid);
        if ($values['isChange']['daysc'])
            $data['days'] = $values['isChange']['days'];
        if ($values['isChange']['paymentc'])
            $data['payment'] = $values['isChange']['payment'];
        if ($values['isChange']['repaymentc'])
            $data['repayment'] = $values['isChange']['repayment'];
        if ($values['isChange']['isAccountc'])
            $data['isAccount'] = $values['isChange']['isAccount'];
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
                $this->context->campService->participants->update($id, $data);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    public function massRemoveSubmitted(SubmitButton $button) {
        if (!array_key_exists("EV_ParticipantCamp_DELETE", $this->availableActions)) {
            $this->flashMessage("Nemáte právo mazat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $values = $button->getForm()->getValues();
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
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
        $form->addText("address", "Adresa")
                ->addRule(Form::FILLED, "Musíš vyplnit adresu.")
                ->getControlPrototype()->placeholder("Ulice č., Město");
        $form->addHidden("aid", $aid);
        $form->addSubmit('send', 'Přidat')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formAddParticipantNewSubmitted(Form $form) {
        if (!array_key_exists("EV_ParticipantCamp_INSERT_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přidávat nové účastníky.", "danger");
            $this->redirect("Default:");
        }

        $values = $form->getValues();
        $aid = $values['aid'];
        $person = array(
            "firstName" => $values['firstName'],
            "lastName" => $values['lastName'],
            "nick" => $values['nick'],
            "note" => $values['address'],
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

