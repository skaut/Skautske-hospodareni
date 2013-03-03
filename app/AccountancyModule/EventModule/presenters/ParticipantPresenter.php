<?php

namespace AccountancyModule\EventModule;

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
        if (!array_key_exists("EV_ParticipantGeneral_ALL_EventGeneral", $this->availableActions)) {
            $this->flashMessage("Nemáte právo prohlížeč účastníky akce", "danger");
            $this->redirect("Event:");
        }

        $participants = $this->context->eventService->participants->getAll($this->aid, $cache = FALSE);
        $list = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        

        usort($participants, function($a, $b) {/* setrizeni podle abecedy */
            return strcasecmp($a->Person, $b->Person);
        });
        natcasesort($list);

        $this->template->participants = $participants;
        $this->template->list = $list;
        $this->template->unit = $unit = $this->context->unitService->getDetail($this->uid);
        $this->template->uparrent = $this->context->unitService->getParrent($unit->ID);
        $this->template->uchildrens = $this->context->unitService->getChild($unit->ID);

        $this->template->accessDeleteParticipant = array_key_exists("EV_ParticipantGeneral_DELETE_EventGeneral", $this->availableActions);
        $this->template->accessUpdateParticipant = array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->template->accessInsertParticipant = array_key_exists("EV_ParticipantGeneral_INSERT_EventGeneral", $this->availableActions);
    }

    public function actionEdit($aid, $pid, $days = 0, $payment = 0) {
        //TODO: kontrola základních udajů
        $form = $this['formEditParticipant'];
        $form->setDefaults(array(
            "days" => $days,
            "payment" => $payment,
            "user" => $pid,
        ));
    }

    public function renderExport($aid) {
        $template = $this->context->exportService->getParticipants($aid, $this->context->eventService);
        $this->context->eventService->participants->makePdf($template, "seznam-ucastniku.pdf");
        $this->terminate();
    }

//    public function renderHpd($aid) {
//        $template = $this->context->exportService->getHpd($aid, $this->context->eventService, $this->context->unitService);
//        $this->context->eventService->participants->makePdf($template, "hpd.pdf");
//        $this->terminate();
//    }

    public function handleRemove($pid) {
        $this->editableOnly();
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
        $this->editableOnly();
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

//    /**
//     * nastavuje počet dní
//     */
//    public function handleEditDays() {
//        $this->editableOnly();
//        $post = $this->context->httpRequest->getPost();
//        $days = (int) $post['days'];
//        $id = (int) $post['id'];
//        $this->context->eventService->participants->setDays($id, $days);
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
//        $this->context->eventService->participants->setPayment($participantId, $payment);
//        echo $payment;
//        $this->terminate();
//    }
//    function handleAddPaymentToChit() {
//        if ($this->context->eventService->participants->addPaymentsToCashbook($this->aid, $this->context->eventService, $this->context->eventService->chits)) {
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
        $form = new Form($this, $name);
        $form->addText("days", "Dní");
        $form->addText("payment", "Částka");
        $form->addHidden("user");
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formEditParticipantSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();
        $arr = array(
            "payment" => $values['payment'],
            "days" => $values['days'],
        );


        $this->context->eventService->participants->update($values['user'], $arr);

        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            $this->invalidateControl("participants");
        } else {
            $this->redirect('default', $this->aid);
        }
    }

    public function createComponentFormMassAdd($name) {
        $participants = $this->context->eventService->participants->getAll($this->aid);
        $all = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);

        $form = new Form($this, $name);

        $group = $form->addContainer('all');
        foreach ($all as $id => $p) {
            $group->addCheckbox($id, $p);
        }
        $form->addSubmit('massAddSend', ' ')
                ->getControlPrototype()->setName("button")->create('i class="icon-plus icon-white"');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formMassAddSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();

        foreach ($values['all'] as $id => $bool) {
            if ($bool)
                $this->context->eventService->participants->add($this->aid, $id);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid)); //TODO je to ok?
    }

    public function createComponentFormMassParticipants($name) {
        $participants = $this->context->eventService->participants->getAll($this->aid);
        
        $form = new Form($this, $name);
        $group = $form->addContainer('ids');
        foreach ($participants as $id => $p) {
            $group->addCheckbox($p->ID, $p->Person);
        }

        $form->addText("days", "dní");
        $form->addText("payment", "částka");
        $form->addSubmit('massRemoveSend', 'Odebrat vybrané')
                ->getControlPrototype()
                ->setName("button")
                ->create('i class="icon-remove icon-white"');
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
                $this->context->eventService->participants->removeParticipant($id);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

    public function massEditSubmitted(SubmitButton $button) {
        $this->editableOnly();
        $values = $button->getForm()->getValues();
        $data = array(
            "days" => $values['days'],
            "payment" => $values['payment']
        );
        foreach ($values['ids'] as $id => $bool) {
            if ($bool)
                $this->context->eventService->participants->update($id, $data);
        }
        $this->redirect('default', array("aid" => $this->aid, "uid" => $this->uid));
    }

//    function createComponentFormFindByName($name) {
//        $aid = $this->presenter->aid;
//        $participants = $this->context->eventService->participants->getAllParticipant($this->aid, TRUE);
//        $all = $this->context->memberService->getAll($this->uid, $this->getDirectMemberOnly(), $participants);
//
//        $form = new Form($this, $name);
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
//    public function formFindByNameSubmitted(Form $form) {
//        $this->editableOnly();
//        $values = $form->getValues();
//        $aid = $values['aid'];
//
//        $this->context->eventService->participants->add($aid, $values['user']);
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
//        $form = new Form($this, $name);
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
//    public function formAddPaymentMassSubmitted(Form $form) {
//        $this->editableOnly();
//        $values = $form->getValues();
//        $aid = $values['aid'];
//        $this->context->eventService->participants->setPaymentMass($aid, $values['sum'], $values['rewrite']);
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
        $this->editableOnly();
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

