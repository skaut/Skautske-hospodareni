<?php

/**
 * @author sinacek
 */
class Accountancy_Event_DefaultPresenter extends Accountancy_Event_BasePresenter {

    public $ses;

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    function startup() {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state))
            $this->ses->state = self::DEFAULT_STATE;
        if (!isset($this->ses->year))
            $this->ses->year = date("Y");
    }

    public function renderDefault() {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : date("Y");
        $state = isset($this->ses->state) ? $this->ses->state : NULL;
        
        $list = $this->context->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $localAvaibleActions = $this->context->userService->actionVerify(self::STable, $value->ID);
            $value->accessDelete = array_key_exists("EV_EventGeneral_DELETE", $localAvaibleActions);
            $value->accessDetail = array_key_exists("EV_EventGeneral_DETAIL", $localAvaibleActions);
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year)
            $this['formFilter']['year']->setDefaultValue($year);
        if ($state)
            $this['formFilter']['state']->setDefaultValue($state);

        $this->template->accessCreate = array_key_exists("EV_EventGeneral_INSERT", $this->availableActions);
    }

    /**
     * mění podmínky filtrování akcí podle roku
     * @param type $year 
     */
    public function handleChangeYear($year) {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }

    /**
     * změní podmínky filtrování akcí podle stavu akce
     * @param type $state 
     */
    public function handleChangeState($state) {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }

    /**
     * zruší akci
     * @param type $aid 
     */
    public function handleCancel($aid) {
        if (!array_key_exists("EV_EventGeneral_UPDATE_Cancel", $this->availableActions)) {
            $this->flashMessage("Nemáte právo na zrušení akce.", "danger");
            $this->redirect("this");
        }

        if ($this->context->eventService->event->cancel($aid)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "danger");
        }

        $this->redirect("this");
    }

    function createComponentFormFilter($name) {
        $states = array_merge(array("all" => "Nezrušené"), $this->context->eventService->event->getStates());
        $years = array("all"=>"Všechny");
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }

        $form = new AppForm($this, $name);
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');

        return $form;
    }

    function formFilterSubmitted(AppForm $form) {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", array("aid" => $this->aid));
    }

    function createComponentFormCreate($name) {
        $scopes = $this->context->eventService->event->getScopes();
        $types = $this->context->eventService->event->getTypes();

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce")
                ->addRule(Form::FILLED, "Musíte vyplnit název akce");
        $form->addDatePicker("start", "Od")
                ->addRule(Form::FILLED, "Musíte vyplnit začátek akce");
        $form->addDatePicker("end", "Do")
                ->addRule(Form::FILLED, "Musíte vyplnit konec akce");
        $form->addText("location", "Místo");
        $form->addSelect("scope", "Rozsah (+)", $scopes)
                ->setDefaultValue("2");
        $form->addSelect("type", "Typ (+)", $types)
                ->setDefaultValue("2");
        $form->addSubmit('send', 'Založit akci')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(AppForm $form) {
        if (!array_key_exists("EV_EventGeneral_INSERT", $this->availableActions)) {
            $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
        $id = $this->context->eventService->event->create(
                $v['name'], $v['start']->format("Y-m-d"), $v['end']->format("Y-m-d"), $v['location'], $unit = NULL, $v['scope'], $v['type']
        );

        if ($id) {
            $this->redirect("Event:", array("aid" => $id));
        }
        $this->redirect("this");
    }

}
