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
        $this->service = new ParticipantService();
        $this->uid = $this->context->httpRequest->getQuery("uid", NULL);
        $as = new ActionService();
        $this->template->isEditable = $this->isEditable = $as->isEditable($this->aid);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    // <editor-fold defaultstate="collapsed" desc="setters and getters">
    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

    // </editor-fold>
    
    function renderDefault($aid, $uid = NULL) {
        $participants = $this->service->getAllParticipants($this->aid);
        $all = $this->service->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
        
        $as = new ActionService();
        $uservice = new UnitService();
        $unit = $uservice->getDetail($this->uid);
        $uparrent = $uservice->getParrent($unit->ID);
        $uchildrens = $uservice->getChild($unit->ID);
        
        $this->template->list = $all;
        $this->template->participants = $participants;
        
        $this->template->uparrent = $uparrent;
        $this->template->unit = $unit;
        $this->template->uchildrens = $uchildrens;
    }

//    function createComponentFormParticipants($name) {
//        $actionId = $this->presenter->aid;
//        $unitId = $this->presenter->uid;
//
//        $form = new AppForm($this, $name);
//        $form->addHidden("actionId", $actionId);
//
//        //generuje pole s ID účastníků
//        $participants = $this->service->getAllParticipants($actionId);
//        foreach ($participants as $p) {
//            $check[$p->ID_Person] = true;
//        }
//
//        //checkboxy pro jednotlivé osoby
//        $group = $form->addContainer("participants");
//        $allInUnit = $this->service->getAll($unitId, $this->getDirectMemberOnly());
//        foreach ($allInUnit as $i) {
//            if (!array_key_exists($i->ID, $check)) {
//                $ch = $group->addCheckbox($i->ID, $i->DisplayName);
//                //$ch->setDisabled()->defaultValue = 1;
//            }
//        }
//
//        $form->addSubmit('send', 'Uložit')
//                ->getControlPrototype()->setClass("btn btn-primary");
//        $form->onSuccess[] = array($this, $name . 'Submitted');
//        return $form;
//    }
//
//    function formParticipantsSubmitted(AppForm $form) {
//        $val = $form->getValues();
//        //dump($val);die();
//        $cnt = 0;
//        foreach ($val['participants'] as $id => $isAdd) {
//            if ($isAdd) {
//                $cnt++;
//                $this->service->addParticipant($val['actionId'], $id);
//            }
//        }
//        $this->flashMessage("Přidali jste $cnt účastníků.");
//        $this->redirect("this", $this->aid);
//    }

    public function handleRemove($pid) {
        $this->onlyEditable();
            
        $this->service->removeParticipant($pid);
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('this');
        }
    }
    
    public function handleAdd($pid) {
        $this->onlyEditable();
        $this->service->addParticipant($this->aid, $pid);
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

    /**
     * nastavuje počet dní
     */
    public function handleEditDays() {
        $this->onlyEditable();
        $post = $this->context->httpRequest->getPost();
        $days = (int) $post['days'];
        $id = (int) $post['id'];
        $this->service->setDays($id, $days);
        if($this->isAjax()){
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        }
        //echo $days;
        //$this->terminate();
    }

    /**
     * nastavuje částku
     */
    public function handleEditPayment() {
        $this->onlyEditable();
        $post = $this->context->httpRequest->getPost();
        $id = (int)$post['id'];
        $payment = (int) $post['payment'];
        $this->service->setPayment($id, $payment);
        echo $payment;
        $this->terminate();
    }

    function createComponentFormAddPaymentMass($name) {
        $aid = $this->presenter->aid;
        $form = new AppForm($this, $name);
        $form->addText("sum", "Částka", NULL, 5)
                ->addRule(Form::FILLED, "Zadejte částku")
                ->setType('number');
        $form->addCheckbox("rewrite", "Přemazat stávající údaje?");
        
        $form->addHidden("aid", $aid);
        $form->addSubmit('send', 'Vyplňit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }
    
    public function formAddPaymentMassSubmitted(AppForm $form) {
        $this->onlyEditable();
        $values = $form->getValues();
        //dump($values);die();
        $aid = $values['aid'];
        $this->service->setPaymentMass($aid, $values['sum'], $values['rewrite']);
        $this->redirect("default", array("aid"=>$aid));
    }
    

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
        $this->onlyEditable();
        $values = $form->getValues();
        $aid = $values['aid'];
        $person = array(
            "firstName" => $values['firstName'],
            "lastName" => $values['lastName'],
            "nick" => $values['nick'],
            "note" => $values['address'],
        );
        $this->service->addParticipantNew($aid, $person);
        $this->redirect("this");
    }
    
    protected function onlyEditable(){
        if(!$this->isEditable){
            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
            $this->redirect("this");
        }
    }


}

