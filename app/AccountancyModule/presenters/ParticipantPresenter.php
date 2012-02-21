<?php

/**
 * @author sinacek
 * účastníci
 */

class Accountancy_ParticipantPresenter extends Accountancy_BasePresenter {

    private $SES_EXPIRATION = "+ 3 days";
    protected $sesNS;
    /**
     * @var UcastnikStorage
     */
    private $ucastnici;
    /**
     *
     * @var Ucetnictvi_UserService
     */
    private $Uservice;

    function startup() {
        parent::startup();
        $this->Uservice = new UserService();
        $this->service = new ActionService();
        $this->ucastnici = $this->service->getUcastnici();

        $ns = Environment::getSession(__CLASS__);
        $ns->setExpiration($this->SES_EXPIRATION);
        $this->sesNS = $ns;
    }

    /**
     *
     * @param string $role - která role je aktuálně vybraná
     */
    function renderDefault($role = array()) {
        //$roles = $this->Uservice->getRoles();
        if($this->sesNS->sg) {
            $selectedGroup = $this->sesNS->sg;
        } elseif(!empty ($roles)) {
            $rolesKeys = array_keys($roles);
            $this->sesNS->sg = $rolesKeys [0];
            $selectedGroup = $rolesKeys [0];
        }
        
        $selectedList = $this->ucastnici->getAll();
        $list = array();

        $selectedListKeys = array_keys($selectedList);
        foreach ($this->Uservice->getList($selectedGroup) as $value) {
            if(!in_array($value->userID, $selectedListKeys)){
                $list[] = $value;
            }
        }
        $this->template->sg = $selectedGroup;
        $this->template->list = $list;
        $this->template->selectedList = $selectedList;
        $this->template->akceName = $this->service->getAction()->name;
        $this->template->roles = $roles;
    }

    /**
     * (ajaxovy) pozadavek pro zmenu aktualne zobrazovane skupiny
     * @param string $group
     */
    function handleGroups($group) {
        $this->sesNS->sg = $group;
        if ($this->isAjax()) {
            $this->invalidateControl("seznam");
            $this->invalidateControl("flashmesages");
        } else {
            $this->redirect('this');
        }
    }

    /**
     * přidá účastníka mezi vybrané
     * @param int $key
     */
    function handleAdd($key) {
        $add = $this->ucastnici->add(new MU((array)$this->Uservice->get($key)));

        if ($this->isAjax()) {
            $this->invalidateControl("seznam");
            //$this->payload->payload = $this->ucastnici->get($ucastnik->username);
           //$this->terminate();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * vyjme účastníka z vybraných
     * @param int $key
     */
    function handleRemove($key) {
        $this->ucastnici->removeUcastnik($key);

        if ($this->isAjax()) {
            $this->invalidateControl("seznam");
            //$this->terminate();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * smaze vsechny účastníky
     */
    function handleClearList() {
        $this->ucastnici->clear();

        if ($this->isAjax()) {
            $this->terminate();
        } else {
            $this->redirect('default');
        }
    }

    /**
     * přidá příjmový doklad do paragonů
     */
    function actionAddToParagon() {
        $p = $this->service->getParagony();
        $date = $this->ucastnici->getDate();
        if(!($date instanceof DateTime53)){
            $date = DateTime53::from($this->ucastnici->getDate());
        }
        $p->add(new Paragon(array('komu' => $this->ucastnici->getPrijal(), 'date' => $date , 'ucel' => 'Účastnické příspěvky', 'price' => $this->totalIn(), 'type' => 'pp')));
        $this->redirect('Paragon:');
    }

    /**
     * stránka s formulářem pro vyplnění částek a jmen pokladníka a přijal
     */
    function renderCastka() {
        $form = $this['castkaForm'];
        $this->template->form = $form;
        $this->template->selectedList = $this->ucastnici->getAll();
        $ac = $this->Uservice->getUsersToAC();
        $this->template->autoCompleter = $ac;
    }

    /**
     * vygeneruje formulář pro zadání částek k jednotlivým účastníkům
     * @param <type> $name
     * @return AppForm
     */
    function createComponentCastkaForm($name) {
        $form = new AppForm($this, $name);
        $defaults = array();
        $selectedList = $this->ucastnici->getList();
        $form->addDatePicker('date', 'Datum', 10)
                ->addRule(Form::FILLED, "Není vyplněno datum");
        $form->addText("pokladnik", "Pokladník", 14)
                ->addRule(Form::FILLED, "Není vyplněn pokladník");
        $form->addText("prijal", "Přijal", 14);
        if($selectedList) {
            $group = $form->addContainer('ucastnici');
            foreach ($selectedList as $key => $item) {
                $input = $group->addText($key, $item, 3)->controlPrototype->class("ucastnik");
                $money = $this->ucastnici->get($key)->m;
                if($money){
                    $defaults['ucastnici'][$key] = $money;
                }
            }
        }
        $date = $this->ucastnici->getDate();
        
            $defaults['date'] = $date ? $date->format('j. n. Y') : "";
        
        //$defaults['date'] = date("j.n.Y", ($date = $this->ucastnici->getDate()) ? strtotime($date) : time());

        $defaults['prijal'] = $this->ucastnici->getPrijal();
        $defaults['pokladnik'] = $this->ucastnici->getPokladnik();
        $form->setDefaults($defaults);
        $form->addSubmit('send', 'Uložit');
        $form->onSuccess[] = array($this, 'castkaFormSubmitted');
        return $form;
    }

    /**
     * zpracuje odeslaný formulář s částkami
     * @param AppForm $form
     */
    function castkaFormSubmitted(AppForm $form) {
        $values = $form->getValues();
        $this->ucastnici->setDate($values['date']);
        $this->ucastnici->setPokladnik($values['pokladnik']);
        $this->ucastnici->setPrijal($values['prijal']);
        foreach ($values['ucastnici'] as $key => $money) {
            $this->ucastnici->updateUcastnik($key, 'm', $money);
        }

        $this->flashMessage("Seznam účastníků je hotov");
        $this->redirect("Default:akce");
    }

    
    /**
     * spočítá celkový příjem
     * @param seznam účastníků pro počítání $list
     * @return int
     */
    public function totalIn($list = NULL) {
        if($list === NULL)
            $list = $this->ucastnici->getAll();

        $totalPrice = 0;
        foreach ($list as $ucastnik)
            $totalPrice += $ucastnik->m;
        return $totalPrice;
    }

}


