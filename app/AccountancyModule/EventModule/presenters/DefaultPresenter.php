<?php

namespace App\AccountancyModule\EventModule;

use Nette\Application\UI\Form;

/**
 * @author sinacek
 */
class DefaultPresenter extends BasePresenter {

    public $ses;

    const DEFAULT_STATE = "draft"; //filtrovani zobrazených položek

    function startup() {
        parent::startup();
        //ochrana $this->aid se provádí již v BasePresenteru
        $this->ses = $this->session->getSection(__CLASS__);
        if (!isset($this->ses->state)) {
            $this->ses->state = self::DEFAULT_STATE;
        }
        if (!isset($this->ses->year)) {
            $this->ses->year = date("Y");
        }
    }

    public function renderDefault() {
        //filtrovani zobrazených položek
        $year = isset($this->ses->year) ? $this->ses->year : date("Y");
        $state = isset($this->ses->state) ? $this->ses->state : NULL;

        $list = $this->context->eventService->event->getAll($year, $state);
        foreach ($list as $key => $value) {//přidání dodatečných atributů
            $localAvaibleActions = $this->context->userService->actionVerify(self::STable, $value->ID);
            $value->accessDelete = $this->isAllowed("EV_EventGeneral_DELETE", $localAvaibleActions);
            $value->accessDetail = $this->isAllowed("EV_EventGeneral_DETAIL", $localAvaibleActions);
            $list[$key] = $value;
        }
        $this->template->list = $list;
        if ($year) {
            $this['formFilter']['year']->setDefaultValue($year);
        }
        if ($state) {
            $this['formFilter']['state']->setDefaultValue($state);
        }

        $this->template->accessCreate = $this->isAllowed("EV_EventGeneral_INSERT");
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
        if (!$this->isAllowed("EV_EventGeneral_UPDATE_Cancel")) {
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
        $years = array("all" => "Všechny");
        foreach (array_reverse(range(2012, date("Y"))) as $y) {
            $years[$y] = $y;
        }

        $form = new Form($this, $name);
        $form->addSelect("state", "Stav", $states);
        $form->addSelect("year", "Rok", $years);
        $form->addSubmit('send', 'Hledat')
                        ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');

        return $form;
    }

    function formFilterSubmitted(Form $form) {
        $v = $form->getValues();
        $this->ses->year = $v['year'];
        $this->ses->state = $v['state'];
        $this->redirect("default", array("aid" => $this->aid));
    }

    function isDateValidator($item, $args) {
        return $item == NULL ? FALSE : TRUE;
    }

    function createComponentFormCreate($name) {
        $scopes = $this->context->eventService->event->getScopes();
        $types = $this->context->eventService->event->getTypes();
        $tmpId = $this->context->skautIS->getUnitId();
        $units = array($tmpId => $this->context->unitService->getDetail($tmpId)->SortName);
        foreach ($this->context->unitService->getChild($tmpId) as $u)
            $units[$u->ID] = "» " . $u->SortName;

        $form = new Form($this, $name);
        $form->addText("name", "Název akce*")
                ->addRule(Form::FILLED, "Musíte vyplnit název akce");
        $form->addDatePicker("start", "Od*")
                ->addRule(Form::FILLED, "Musíte vyplnit začátek akce")
                ->addRule(callback('MyValidators::isValidDate'), 'Vyplňte platné datum.');
        $form->addDatePicker("end", "Do*")
                ->addRule(Form::FILLED, "Musíte vyplnit konec akce")
                ->addRule(callback('MyValidators::isValidDate'), 'Vyplňte platné datum.');
        $form->addText("location", "Místo");
        $form->addSelect("orgID", "Pořádající jednotka", $units);
        $form->addSelect("scope", "Rozsah (+)", $scopes)
                ->setDefaultValue("2");
        $form->addSelect("type", "Typ (+)", $types)
                ->setDefaultValue("2");
        $form->addSubmit('send', 'Založit novou akci')
                        ->setAttribute("class", "btn btn-primary btn-large");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(Form $form) {
        if (!$this->isAllowed("EV_EventGeneral_INSERT")) {
            $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
            $this->redirect("this");
        }
        $v = $form->getValues();
//        if($v->start == NULL){
//            $this->flashMessage("Neplatné datum začátku akce.", "error");
//            $this->redirect("this");
//        }
//        if($v->end == NULL){
//            $this->flashMessage("Neplatné datum konce akce.", "error");
//            $this->redirect("this");
//        }
//        if($v->end < $v->start){
//            $this->flashMessage("Nelze založit akci, která dříve skončí nežli začne.", "error");
//            $this->redirect("this");
//        }
        $id = $this->context->eventService->event->create(
                $v['name'], $v['start']->format("Y-m-d"), $v['end']->format("Y-m-d"), $v['location'], $v->orgID, $v['scope'], $v['type']
        );

        if ($id) {
            $this->redirect("Event:", array("aid" => $id));
        }
        $this->redirect("this");
    }
    
    public function actionExportSummary(){
        $this->context->excelService->getEventSummaries(array(255, 274, 261, 355), $this->context->eventService);
        //$this->context->excelService->getEventSummaries(array(51, 70), $this->context->eventService);
    }

}
