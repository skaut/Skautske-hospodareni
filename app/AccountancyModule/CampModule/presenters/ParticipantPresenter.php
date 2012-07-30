<?php

/**
 * @author Hána František
 * účastníci
 */
class Accountancy_Camp_ParticipantPresenter extends Accountancy_Camp_BasePresenter {

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

        $participants   = $this->context->campService->participants->getAll($this->aid, $cache = FALSE);
        $all            = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
        $unit           = $this->context->unitService->getDetail($this->uid);
        $uparrent       = $this->context->unitService->getParrent($unit->ID);
        $uchildrens     = $this->context->unitService->getChild($unit->ID);

        $this->template->uparrent = $uparrent;
        $this->template->unit = $unit;
        $this->template->uchildrens = $uchildrens;
        $this->template->list = $all;
        $this->template->participants = $participants;
        $this->template->accessDeleteParticipant = array_key_exists("EV_ParticipantCamp_DELETE", $this->availableActions);
        $this->template->accessUpdateParticipant = array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions);
        $this->template->accessInsertParticipant = array_key_exists("EV_ParticipantCamp_INSERT_EventCamp", $this->availableActions);
    }

    public function actionEdit($aid, $pid, $days = 0, $payment = 0) {
        $detail = $this->context->campService->participants->getPersonsDays($aid);
        $form = $this['formEditParticipant'];
        $form->setDefaults(array(
            "days" => $days,
            "payment" => $payment,
            "user" => $pid,
        ));
    }

    public function renderExport($aid) {
        $actionInfo = $this->context->campService->event->get($aid);
        $participants = $this->context->campService->participants->getAll($aid);
        $list = $this->context->campService->participants->getAllDetail($aid, $participants);

        $template = $this->template;
        $template->info = $actionInfo;
        $template->list = $list;
        $template->setFile(dirname(__FILE__) . '/../templates/Participant/export.latte');
        $this->context->campService->participants->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_ucastnici.pdf");
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

//    /**
//     * nastavuje počet dní
//     */
//    public function handleEditDays() {
//        $this->editableOnly();
//        $post = $this->context->httpRequest->getPost();
//        $days = (int) $post['days'];
//        $id = (int) $post['id'];
//        $this->context->campService->participants->setDays($id, $days);
////        if ($this->isAjax()) {
////            $this->invalidateControl("potencialParticipants");
////            $this->invalidateControl("participants");
////        }
//        echo $days;
//        $this->terminate();
//    }
//
//    /**
//     * nastavuje částku
//     */
//    public function handleEditPayment() {
//        $this->editableOnly();
//        $post = $this->context->httpRequest->getPost();
//        $participantId = (int) $post['id'];
//        $payment = (int) $post['payment'];
//        $this->context->campService->participants->setPayment($participantId, $payment);
//        echo $payment;
//        $this->terminate();
//    }
//    function handleAddPaymentToChit() {
//        if ($this->context->campService->participants->addPaymentsToCashbook($this->aid, $this->context->campService, $this->context->eventService->chits)) {
//            $this->flashMessage("Přijmy byly přidány do pokladní knihy");
//        } else {
//            $this->flashMessage("Nepodařilo se přidat příjmy do pokladní knihy", "warning");
//        }
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("flash");
//        } else {
//            $this->redirect('Cashbook:', array("aid" => $this->aid));
//        }
//    }

    public function createComponentFormEditParticipant($name) {
        $form = new AppForm($this, $name);
        $form->addText("days", "Dní");
        $form->addText("payment", "Částka");
        $form->addHidden("user");
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formEditParticipantSubmitted(AppForm $form) {
        if (!array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo přidávat účastníky.", "danger");
            $this->redirect("Default:");
        }
        
        $values = $form->getValues();
        $arr = array(
            "Note" => $values['payment'],
            "Days" => $values['days'],
            "Real" => FALSE,
        );


        $this->context->campService->participants->update($values['user'], $arr);

        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('default', $this->aid);
        }
    }

    public function createComponentFormMassAdd($name) {
        $participants = $this->context->campService->participants->getAll($this->aid);
        $all = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        $form = new AppForm($this, $name);

        $group = $form->addContainer('all');
        foreach ($all as $id => $p) {
            $group->addCheckbox($id, $p);
        }

        $form->addSubmit('massAddSend', 'Přidat vybrané')
                ->getControlPrototype()->setClass("btn btn-info btn-small");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassAddSubmitted(AppForm $form) {
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

        $form = new AppForm($this, $name);

        $group = $form->addContainer('ids');
        foreach ($participants as $id => $p) {
            $group->addCheckbox($p->ID, $p->Person);
        }

        $form->addText("days", "dní");
        $form->addText("payment", "částka");

        $form->addSubmit('massRemoveSend', 'Odebrat vybrané')
                ->getControlPrototype()->setClass("btn btn-danger btn-small");
        $form['massRemoveSend']->onClick[] = callback($this, 'massRemoveSubmitted');

        $form->addSubmit('massEditSend', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-info btn-small");
        $form['massEditSend']->onClick[] = callback($this, 'massEditSubmitted');

//        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
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

    public function massEditSubmitted(SubmitButton $button) {
        if (!array_key_exists("EV_ParticipantCamp_UPDATE_EventCamp", $this->availableActions)) {
            $this->flashMessage("Nemáte právo upravovat účastníky.", "danger");
            $this->redirect("Default:");
        }
        $values = $button->getForm()->getValues();
        $data = array(
            "Days" => $values['days'],
            ParticipantService::PAYMENT => $values['payment']
        );
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
                $this->context->campService->participants->update($id, $data);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

//    function createComponentFormFindByName($name) {
//        $aid = $this->presenter->aid;
//        $participants = $this->context->campService->participants->getAllParticipant($this->aid, TRUE);
//        $all = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
//
//        $form = new AppForm($this, $name);
//        $form->addSelect("user", "Jméno", (array) $all)
//                ->setPrompt("Vyber");
//        $form->addHidden("aid", $aid);
//        $form->addSubmit('send', 'Přidat')
//                ->getControlPrototype()
//                ->setName("button")
//                ->setHtml('<i class="icon-plus icon-white"></i>')
//                ->setClass("btn btn-primary btn-mini");
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//        return $form;
//    }
//
//    public function formFindByNameSubmitted(AppForm $form) {
//        $this->editableOnly();
//        $values = $form->getValues();
//        $aid = $values['aid'];
//
//        $this->context->campService->participants->add($aid, $values['user']);
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("potencialParticipants");
//            $this->invalidateControl("participants");
//        } else {
//            $this->redirect('this');
//        }
//    }
//    function createComponentFormAddPaymentMass($name) {
//        $aid = $this->presenter->aid;
//        $form = new AppForm($this, $name);
//        $form->addText("sum", "Částka", NULL, 5)
//                ->addRule(Form::FILLED, "Zadejte částku")
//                ->setType('number');
//        $form->addCheckbox("rewrite", "Přemazat stávající údaje?");
//
//        $form->addHidden("aid", $aid);
//        $form->addSubmit('send', 'Vyplňit')
//                ->getControlPrototype()->setClass("btn btn-primary");
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//        return $form;
//    }
//
//    public function formAddPaymentMassSubmitted(AppForm $form) {
//        $this->editableOnly();
//        $values = $form->getValues();
//        $aid = $values['aid'];
//        $this->context->campService->participants->setPaymentMass($aid, $values['sum'], $values['rewrite']);
//        if ($this->isAjax()) {
//            $this->invalidateControl("participants");
////            $this->invalidateControl("potencialParticipants");
//        } else {
//            $this->redirect("default", array("aid" => $aid));
//        }
//    }

    /**
     * formulář na přidání nové osoby
     * @param string $name
     * @return AppForm
     */
    function createComponentFormAddParticipantNew($name) {
        $aid = $this->presenter->aid;
        $form = new AppForm($this, $name);
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

    public function formAddParticipantNewSubmitted(AppForm $form) {
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

