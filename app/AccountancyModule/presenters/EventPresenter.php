<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_EventPresenter extends Accountancy_BasePresenter {

    public $ses;


    function startup() {
        parent::startup();
        $this->ses = $this->session->getSection(__CLASS__);
        //ochrana $this->aid se provádí již v BasePresenteru
    }

    public function renderDefault() {
        $year = isset ($this->ses->year) ? $this->ses->year : NULL;
        $state = isset ($this->ses->state) ? $this->ses->state : NULL;
        $list = $this->context->eventService->getAll($year, $state);
        $this->template->list = $list;
        if($year)
            $this['formFilter']['year']->setDefaultValue($year);
        if($state)
            $this['formFilter']['state']->setDefaultValue($state);
    }

    public function renderInfo($aid) {
        $data = $this->context->eventService->get($aid);

        //nastavení dat do formuláře pro editaci
        $func = $this->context->eventService->getFunctions($aid);
        $form = $this['formEdit'];
        $form->setDefaults(array(
            "aid" => $aid,
            "name" => $data->DisplayName,
            "start" => $data->StartDate,
            "end" => $data->EndDate,
            "location" => $data->Location,
            "leader" => $func[EventService::LEADER]->ID_Person,
            "assistant" => $func[EventService::ASSISTANT]->ID_Person,
            "economist" => $func[EventService::ECONOMIST]->ID_Person,
        ));

        $this->template->data = $data;
        $this->template->funkce = $func;
        $this->template->isEditable = $this->context->eventService->isEditable($data);
    }

    public function actionOpen($aid) {
        $res = $this->context->eventService->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("info", array("aid" => $this->aid));
    }

    public function actionClose($aid) {
        if ($this->context->eventService->isCloseable($aid)) {
            $res = $this->context->eventService->close($aid);
            $this->flashMessage("Akce byla uzavřena.");
        } else {
            $this->flashMessage("Před uzavřením akce musíte vyplnit vedoucího akce", "danger");
        }

        $this->redirect("info", array("aid" => $aid));
    }

    public function handleCancel($aid) {
        if ($this->context->eventService->cancel($aid)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "danger");
        }

        $this->redirect("this");
    }
    
    public function handleChangeYear($year) {
        $this->ses->year = $year;
        if($this->isAjax()){
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }
    
    public function handleChangeState($state) {
        $this->ses->state = $state;
        if($this->isAjax()){
            $this->invalidateControl("events");
        } else {
            $this->redirect("this");
        }
    }
    
    function createComponentFormFilter($name) {
        $states = $this->context->eventService->getStates();
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
        $this->redirect("default", array("aid"=>$this->aid));
    }


    function createComponentFormCreate($name) {
        $combo = $this->context->userService->getCombobox();
        $scopes = $this->context->eventService->getScopes();
        $types = $this->context->eventService->getTypes();
        

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce")
                ->addRule(Form::FILLED, "Musíte vyplnit název akce");
        $form->addDatePicker("start", "Od")
                ->addRule(Form::FILLED, "Musíte vyplnit začátek akce");
        $form->addDatePicker("end", "Do")
                ->addRule(Form::FILLED, "Musíte vyplnit konec akce");
        $form->addText("location", "Místo");
        $form->addSelect("scope", "Rozsah", $scopes)
                ->setDefaultValue("2");
        $form->addSelect("type", "Rozsah", $types)
                ->setDefaultValue("2");
        $form->addSelect("leader", "Vedoucí akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("assistant", "Zástupce ved. akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("economist", "Hospodář", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSubmit('send', 'Založit akci')
                ->getControlPrototype()->setClass("btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(AppForm $form) {
        $v = $form->getValues();

        try {
            $id = $this->context->eventService->create(
                    $v['name'],
                    $v['start']->format("Y-m-d"),
                    $v['end']->format("Y-m-d"),
                    $v['location'],
                    $v['leader'],
                    $v['assistant'],
                    $v['economist'],
                    $v['scope'],
                    $v['type']
            );
        } catch (SkautIS_Exception $e) {
            if (preg_match("/UnitPermissionDenied/", $e->getMessage())) {
                $this->flashMessage("Nemáte oprávnění pro založení akce", "danger");
                $this->redirect("this");
            }
            throw $e;
        }

        if ($id) {
            $this->flashMessage("Akce byla založena");
            $this->redirect("default");
        }
        $this->redirect("this");
    }

    function createComponentFormEdit($name) {
        $combo = $this->context->userService->getCombobox(NULL, TRUE);

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
        $form->addText("location", "Místo");
        $form->addSelect("leader", "Vedoucí akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("assistant", "Zástupce ved. akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("economist", "Hospodář", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addHidden("aid");
        $form->addSubmit('send', 'Upravit')
                ->getControlPrototype()->setClass("btn btn-primary");

        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(AppForm $form) {
        $values = $form->getValues();

        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        try {
            $id = $this->context->eventService->update($values);
        } catch (SkautIS_PermissionException $exc) {
            $this->flashMessage($exc->getMessage(), "danger");
            $this->redirect("Action:");
        } catch (SkautIS_Exception $e) {
            if (preg_match("/EventFunction_LeaderMustBeAdult/", $e->getMessage())) {//dospělost vedoucího akce
                $this->flashMessage("Vedoucí akce musí být dosplělá osoba.", "danger");
            } elseif (preg_match("/EventFunction_AssistantMustBeAdult/", $e->getMessage())) { //dospělost zástupce
                $this->flashMessage("Zástupce musí být dosplělá osoba.", "danger");
            } else {
                throw $e;
            }
            $this->redirect("this");
        }

        if ($id) {
            $this->flashMessage("Upravili jste základní údaje");
            $this->redirect("info", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Akci se nepodařilo upravit", "danger");
        }
        $this->redirect("this");
    }

}

