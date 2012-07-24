<?php

/**
 * @author sinacek
 */
class Accountancy_Camp_DefaultPresenter extends Accountancy_Camp_BasePresenter  {
    
    public $ses;

    const DEFAULT_STATE = "approvedParent"; //filtrovani zobrazených položek

    function startup() {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state))
            $this->ses->state = self::DEFAULT_STATE;
    }

    public function renderDefault() {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : NULL;
        $state = isset($this->ses->state) ? $this->ses->state : NULL;
        if ($state == "all")
            $state = NULL;
        $list = $this->context->campService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $value->accessDelete = $this->context->userService->actionVerify(self::STable, $value->ID, self::STable . "_DELETE");
            $value->accessDetail = $this->context->userService->actionVerify(self::STable, $value->ID, self::STable . "_DETAIL");
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year)
            $this['formFilter']['year']->setDefaultValue($year);
        if ($state)
            $this['formFilter']['state']->setDefaultValue($state);

        //$this->template->accessCreate = array_key_exists("EV_EventGeneral_INSERT", $this->availableActions);
    }
    
    public function handleChangeYear($year) {
        $this->ses->year = $year;
        if ($this->isAjax()) {
            $this->invalidateControl("camps");
        } else {
            $this->redirect("this");
        }
    }

    public function handleChangeState($state) {
        $this->ses->state = $state;
        if ($this->isAjax()) {
            $this->invalidateControl("camps");
        } else {
            $this->redirect("this");
        }
    }
    function createComponentFormFilter($name) {
        $states = array_merge(array("all" => "Nezrušené"), $this->context->campService->event->getStates());
        $years = array();
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
    
}
