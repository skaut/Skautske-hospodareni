<?php

/**
 * @author Hána František
 * účastníci
 */
class Accountancy_ParticipantPresenter extends Accountancy_BasePresenter {
//    private $SES_EXPIRATION = "+ 3 days";
    //protected $sesNS;

    /**
     * číslo aktuální jendotky
     * @var int
     */
    protected $uid;

    function startup() {
        parent::startup();
        $this->service = new ParticipantService();
//        $this->Uservice = new UserService();
//        $this->service = new ActionService();
//        $this->ucastnici = $this->service->getUcastnici();
//
//        $ns = $this->session->getSection(__CLASS__);
//        $ns->setExpiration($this->SES_EXPIRATION);
//        $this->sesNS = $ns;
        $this->uid = $this->context->httpRequest->getQuery("uid", NULL);
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    protected function getDirectMemberOnly() {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct) {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }

    function renderDefault($aid, $uid = NULL) {
        $this->template->participants = $this->service->getAllParticipants($this->aid);
        $data = $this->service->getAll($this->uid, $this->getDirectMemberOnly());

        $uservice = new UnitService();
        $unit = $uservice->getDetail($this->uid);
        $uparrent = $uservice->getParrent($unit->ID);
        $uchildrens = $uservice->getChild($unit->ID);

        $this->template->list = $data;

        $this->template->uparrent = $uparrent;
        $this->template->unit = $unit;
        $this->template->uchildrens = $uchildrens;

//        //$roles = $this->Uservice->getRoles();
//        if($this->sesNS->sg) {
//            $selectedGroup = $this->sesNS->sg;
//        } elseif(!empty ($roles)) {
//            $rolesKeys = array_keys($roles);
//            $this->sesNS->sg = $rolesKeys [0];
//            $selectedGroup = $rolesKeys [0];
//        }
//        
//        $selectedList = $this->ucastnici->getAll();
//        $list = array();
//
//        $selectedListKeys = array_keys($selectedList);
//        foreach ($this->Uservice->getList($selectedGroup) as $value) {
//            if(!in_array($value->userID, $selectedListKeys)){
//                $list[] = $value;
//            }
//        }
//        $this->template->sg = $selectedGroup;
//        $this->template->list = $list;
//        $this->template->selectedList = $selectedList;
//        $this->template->akceName = $this->service->getAction()->name;
//        $this->template->roles = $roles;
    }

    function createComponentFormParticipants($name) {
        $actionId = $this->presenter->aid;
        $unitId = $this->presenter->uid;

        $form = new AppForm($this, $name);
        $form->addHidden("actionId", $actionId);

        //generuje pole s ID účastníků
        $participants = $this->service->getAllParticipants($actionId);
        foreach ($participants as $p) {
            $check[$p->ID_Person] = true;
        }

        //checkboxy pro jednotlivé osoby
        $group = $form->addContainer("participants");
        foreach ($this->service->getAll($unitId, $this->getDirectMemberOnly()) as $i) {
            $ch = $group->addCheckbox($i->ID, $i->DisplayName);
            if (array_key_exists($i->ID, $check)) {
                $ch->setDisabled()->defaultValue = 1;
            }
        }

        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formParticipantsSubmitted(AppForm $form) {
        $val = $form->getValues();
        //dump($val);die();
        $cnt = 0;
        foreach ($val['participants'] as $id => $bool) {
            if ($bool) {
                $cnt++;
                $this->service->addParticipant($val['actionId'], $id);
            }
        }

        $this->flashMessage("Přidali jste $cnt účastníků.");
        $this->redirect("this", $this->aid);
    }

    public function handleRemoveParticipant($pid) {
        if ($this->isAjax()) {
            $this->service->removeParticipant($pid);
            //$this->invalidateControl("seznam");
            //$this->terminate();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * mění stav jestli nabízet jenom přímé členy
     */
    public function handleChangeDirectMemberOnly() {
        $this->setDirectMemberOnly(!$this->getDirectMemberOnly());
        if ($this->isAjax()) {
            $this->invalidateControl("potencialParticipants");
            //$this->terminate();
        } else {
            $this->redirect('this', array("aid" => $this->aid, "uid" => $this->uid));
        }
    }

    /**
     * nastavuje počet dní
     */
    public function handleEditDays() {
        $post = $this->context->httpRequest->getPost();
        $id = (int) str_replace("par-days-", "", $post['id']);
        $days = (int) $post['days'];
        $this->service->setDays($id, $days);
        echo $days;
        $this->terminate();
    }

    /**
     * nastavuje částku
     */
    public function handleEditPayment() {
        $post = $this->context->httpRequest->getPost();
        //dump($post);$this->terminate();

        $id = (int) str_replace("par-payment-", "", $post['id']);
        $payment = (int) $post['payment'];
        $this->service->setPayment($id, $payment);
        echo $payment;
        $this->terminate();
    }
    
    public function actionPaymentMass($aid){
        
    }


    function createComponentFormAddPaymentMass($name) {
        $aid = $this->presenter->aid;
        $form = new AppForm($this, $name);
        $form->addText("sum", "Částka", 6)
                ->addRule(Form::FILLED, "Zadejte částku")
                ->setType('number')
                ->getControlPrototype()->placeholder("bez Kč");
        $form->addCheckbox("rewrite", "Přemazat stávající údaje?");
        
        $form->addHidden("aid", $aid);
        $form->addSubmit('send', 'Vyplňit');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }
    
    public function formAddPaymentMassSubmitted(AppForm $form) {
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
        $form->addSubmit('send', 'Přidat');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    public function formAddParticipantNewSubmitted(AppForm $form) {
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

//    /**
//     * (ajaxovy) pozadavek pro zmenu aktualne zobrazovane skupiny
//     * @param string $group
//     */
//    function handleGroups($group) {
//        $this->sesNS->sg = $group;
//        if ($this->isAjax()) {
//            $this->invalidateControl("seznam");
//            $this->invalidateControl("flashmesages");
//        } else {
//            $this->redirect('this');
//        }
//    }
//
//    /**
//     * přidá účastníka mezi vybrané
//     * @param int $key
//     */
//    function handleAdd($key) {
//        $add = $this->ucastnici->add(new MU((array) $this->Uservice->get($key)));
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("seznam");
//            //$this->payload->payload = $this->ucastnici->get($ucastnik->username);
//            //$this->terminate();
//        } else {
//            $this->redirect('this');
//        }
//    }
//
//    /**
//     * vyjme účastníka z vybraných
//     * @param int $key
//     */
//    function handleRemove($key) {
//        $this->ucastnici->removeUcastnik($key);
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("seznam");
//            //$this->terminate();
//        } else {
//            $this->redirect('this');
//        }
//    }
//
//    /**
//     * smaze vsechny účastníky
//     */
//    function handleClearList() {
//        $this->ucastnici->clear();
//
//        if ($this->isAjax()) {
//            $this->terminate();
//        } else {
//            $this->redirect('default');
//        }
//    }
//
//    /**
//     * přidá příjmový doklad do paragonů
//     */
//    function actionAddToParagon() {
//        $p = $this->service->getParagony();
//        $date = $this->ucastnici->getDate();
//        if (!($date instanceof DateTime53)) {
//            $date = DateTime53::from($this->ucastnici->getDate());
//        }
//        $p->add(new Paragon(array('komu' => $this->ucastnici->getPrijal(), 'date' => $date, 'ucel' => 'Účastnické příspěvky', 'price' => $this->totalIn(), 'type' => 'pp')));
//        $this->redirect('Paragon:');
//    }
//
//    /**
//     * stránka s formulářem pro vyplnění částek a jmen pokladníka a přijal
//     */
//    function renderCastka() {
//        $form = $this['castkaForm'];
//        $this->template->form = $form;
//        $this->template->selectedList = $this->ucastnici->getAll();
//        $ac = $this->Uservice->getUsersToAC();
//        $this->template->autoCompleter = $ac;
//    }
//
//    /**
//     * vygeneruje formulář pro zadání částek k jednotlivým účastníkům
//     * @param <type> $name
//     * @return AppForm
//     */
//    function createComponentCastkaForm($name) {
//        $form = new AppForm($this, $name);
//        $defaults = array();
//        $selectedList = $this->ucastnici->getList();
//        $form->addDatePicker('date', 'Datum', 10)
//                ->addRule(Form::FILLED, "Není vyplněno datum");
//        $form->addText("pokladnik", "Pokladník", 14)
//                ->addRule(Form::FILLED, "Není vyplněn pokladník");
//        $form->addText("prijal", "Přijal", 14);
//        if ($selectedList) {
//            $group = $form->addContainer('ucastnici');
//            foreach ($selectedList as $key => $item) {
//                $input = $group->addText($key, $item, 3)->controlPrototype->class("ucastnik");
//                $money = $this->ucastnici->get($key)->m;
//                if ($money) {
//                    $defaults['ucastnici'][$key] = $money;
//                }
//            }
//        }
//        $date = $this->ucastnici->getDate();
//
//        $defaults['date'] = $date ? $date->format('j. n. Y') : "";
//
//        //$defaults['date'] = date("j.n.Y", ($date = $this->ucastnici->getDate()) ? strtotime($date) : time());
//
//        $defaults['prijal'] = $this->ucastnici->getPrijal();
//        $defaults['pokladnik'] = $this->ucastnici->getPokladnik();
//        $form->setDefaults($defaults);
//        $form->addSubmit('send', 'Uložit');
//        $form->onSuccess[] = array($this, 'castkaFormSubmitted');
//        return $form;
//    }
//
//    /**
//     * zpracuje odeslaný formulář s částkami
//     * @param AppForm $form
//     */
//    function castkaFormSubmitted(AppForm $form) {
//        $values = $form->getValues();
//        $this->ucastnici->setDate($values['date']);
//        $this->ucastnici->setPokladnik($values['pokladnik']);
//        $this->ucastnici->setPrijal($values['prijal']);
//        foreach ($values['ucastnici'] as $key => $money) {
//            $this->ucastnici->updateUcastnik($key, 'm', $money);
//        }
//
//        $this->flashMessage("Seznam účastníků je hotov");
//        $this->redirect("Default:akce");
//    }
//    /**
//     * spočítá celkový příjem
//     * @param seznam účastníků pro počítání $list
//     * @return int
//     */
//    public function totalIn($list = NULL) {
//        if ($list === NULL)
//            $list = $this->ucastnici->getAll();
//
//        $totalPrice = 0;
//        foreach ($list as $ucastnik)
//            $totalPrice += $ucastnik->m;
//        return $totalPrice;
//    }
}

