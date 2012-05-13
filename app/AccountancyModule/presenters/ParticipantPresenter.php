<?php

/**
 * @author Hána František
 * účastníci
 */
class Accountancy_ParticipantPresenter extends Accountancy_BasePresenter {

    /**
     * číslo aktuální jendotky
     * @var int
     */
    protected $uid;

    /**
     * je akce editovatelná?
     * @var bool
     */
    protected $isEditable;

    function startup() {
        parent::startup();

        if (!$this->aid) {
            $this->flashMessage("Nepovolený přístup", "error");
            $this->redirect("Event:");
        }
        $this->uid = $this->getParameter("uid", NULL);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    function renderDefault($aid, $uid = NULL) {
        $participants = $this->context->participantService->getAllParticipant($this->aid, $cache = FALSE);
        $all = $this->context->participantService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        $unit = $this->context->unitService->getDetail($this->uid);
        $uparrent = $this->context->unitService->getParrent($unit->ID);
        $uchildrens = $this->context->unitService->getChild($unit->ID);

        $this->template->uparrent = $uparrent;
        $this->template->unit = $unit;
        $this->template->uchildrens = $uchildrens;
        $this->template->list = $all;
        $this->template->participants = $participants;
    }

    public function actionEdit($aid, $pid, $days = 0, $payment = 0) {
        $detail = $this->context->participantService->getPersonsDays($aid);
        $form = $this['formEditParticipant'];
        $form->setDefaults(array(
            "days" => $days,
            "payment" => $payment,
            "user" => $pid,
        ));
    }
    
    public function renderExport($aid){
        $actionInfo = $this->context->eventService->get($aid);
        $participants = $this->context->participantService->getAllParticipant($aid);
        $list = $this->context->participantService->getAllDetail($aid, $participants);

        $template = $this->template;
        $template->info = $actionInfo;
        $template->list = $list;
        $template->setFile(dirname(__FILE__) . '/../templates/Participant/export.latte');
        $this->context->participantService->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_ucastnici.pdf");
        $this->terminate();
    }
    
    public function renderHpd($aid){
        $actionInfo = $this->context->eventService->get($aid);
        $list = $this->context->participantService->getAllParticipant($aid);
        $template = $this->template;
        $template->oficialName = $this->context->unitService->getOficialName($actionInfo->ID_Unit);
        $template->totalPayment = $this->context->participantService->getTotalPayment($aid);
        $template->list = $list;
        $template->setFile(dirname(__FILE__) . '/../templates/Participant/ex.hpd.latte');
        $this->context->participantService->makePdf($template, Strings::webalize($actionInfo->DisplayName) . "_hpd.pdf");
        $this->terminate();
    }

    public function handleRemove($pid) {
        $this->editableOnly();

        $this->context->participantService->removeParticipant($pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
//            $this->invalidateControl("flash");
        } else {
            $this->redirect('this');
        }
    }

    public function handleAdd($pid) {
        $this->editableOnly();
        $this->context->participantService->add($this->aid, $pid);
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
//        $this->context->participantService->setDays($id, $days);
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
//        $this->context->participantService->setPayment($participantId, $payment);
//        echo $payment;
//        $this->terminate();
//    }

    function handleAddPaymentToChit() {
        if ($this->context->participantService->addPaymentsToCashbook($this->aid, $this->context->eventService, $this->context->chitService)) {
            $this->flashMessage("Přijmy byly přidány do pokladní knihy");
        } else {
            $this->flashMessage("Nepodařilo se přidat příjmy do pokladní knihy", "warning");
        }

        if ($this->isAjax()) {
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', array("aid" => $this->aid, "uid" => $this->uid));
        }
    }

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
        $this->editableOnly();
        $values = $form->getValues();
        $arr = array(
            "Note" => $values['payment'],
            "Days" => $values['days'],
        );

        $this->context->participantService->update($values['user'], $arr);

        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('default', $this->aid);
        }
    }

    public function createComponentFormMassAdd($name) {
        $participants = $this->context->participantService->getAllParticipant($this->aid);
        $all = $this->context->participantService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        $form = new AppForm($this, $name);

        $group = $form->addContainer('all');
        foreach ($all as $id => $p) {
            $input = $group->addCheckbox($id);
        }

        $form->addSubmit('massAddSend', 'Přidat vybrané')
                ->getControlPrototype()->setClass("btn btn-info btn-small");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassAddSubmitted(AppForm $form) {
        $this->editableOnly();
        $values = $form->getValues();

        foreach ($values['all'] as $id => $bool) {
            if ($bool)
                $this->context->participantService->add($this->aid, $id);
        }
        $this->redirect('default', array("aid"=>$this->aid, "uid"=>$this->uid));//TODO je to ok?
    }
    
    public function createComponentFormMassParticipants($name) {
        $participants = $this->context->participantService->getAllParticipant($this->aid);

        $form = new AppForm($this, $name);

        $group = $form->addContainer('ids');
        foreach ($participants as $id => $p) {
            $input = $group->addCheckbox($p->ID);
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
        $this->editableOnly();
        $values = $button->getForm()->getValues();
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
                $this->context->participantService->removeParticipant($id);
        }
        $this->redirect('default', array("aid"=>$this->aid, "uid"=>$this->uid));
    }
    
    public function massEditSubmitted(SubmitButton $button) {
        $this->editableOnly();
        $values = $button->getForm()->getValues();
        $data = array(
            "Days" => $values['days'],
            ParticipantService::PAYMENT => $values['payment']
        );
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
                $this->context->participantService->update($id, $data);
        }
        $this->redirect('default', array("aid"=>$this->aid, "uid"=>$this->uid));
    }

//    function createComponentFormFindByName($name) {
//        $aid = $this->presenter->aid;
//        $participants = $this->context->participantService->getAllParticipant($this->aid, TRUE);
//        $all = $this->context->participantService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
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
//        $this->context->participantService->add($aid, $values['user']);
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
//        $this->context->participantService->setPaymentMass($aid, $values['sum'], $values['rewrite']);
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
        $this->editableOnly();
        $values = $form->getValues();
        $aid = $values['aid'];
        $person = array(
            "firstName" => $values['firstName'],
            "lastName" => $values['lastName'],
            "nick" => $values['nick'],
            "note" => $values['address'],
        );
        $this->context->participantService->addNew($aid, $person);
        $this->redirect("this");
    }

    // <editor-fold defaultstate="collapsed" desc="setters and getters">
    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

    // </editor-fold>
}

