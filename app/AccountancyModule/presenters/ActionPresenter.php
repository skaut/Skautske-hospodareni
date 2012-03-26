<?php

/**
 * @author Hána František
 * akce
 */
class Accountancy_ActionPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        /** @var ActionService */
        $this->service = new ActionService();
    }
    
    public function beforeRender() {
        parent::beforeRender();
    }

    public function actionList() {
        $list = $this->service->getMyActions();
        //dump($unit);
        if (empty($list)) {
            $this->redirect("create");
        }
        $this->template->list = $list;
    }

    public function renderCreate() {
        
    }
    
    public function actionOpen($aid) {
        $res = $this->service->open($aid);
        $this->flashMessage("Akce byla znovu otevřena.");
        $this->redirect("view", array("aid"=> $this->aid));
    }

    public function renderEdit($aid) {
        $action = $this->service->get($aid);
        $func = $this->service->getFunctions($aid);

        $form = $this['formEdit'];
        $form->setDefaults(array(
            "aid"   => $aid,
            "name"  => $action->DisplayName,
            "start" => $action->StartDate,
            "end"   => $action->EndDate,
            "leader"    =>  $func[0]->ID_Person,
            "assistant" =>  $func[1]->ID_Person,
            "economist" =>  $func[2]->ID_Person,
        ));
    }

    function createComponentFormCreate($name) {
        $us = new UserService();
        $combo = $us->getCombobox();

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
        $form->addSelect("leader", "Vedoucí akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("assistant", "Zástupce ved. akce", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSelect("economist", "Hospodář", $combo)
                ->setPrompt("Vyber")
                ->getControlPrototype()->setClass("combobox");
        $form->addSubmit('send', 'Založit akci');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(AppForm $form) {
        $values = $form->getValues();

        $id = $this->service->create(
                $values['name'], $values['start']->format("Y-m-d"), $values['end']->format("Y-m-d"), $values['leader'], $values['assistant'], $values['economist']
        );

        if ($id) {
            $this->flashMessage("Akce byla založena");
            $this->redirect("list");
        } else {
            $this->flashMessage("Akci se nepodařilo založit", "fail");
        }
        $this->redirect("this");
    }

    function createComponentFormEdit($name) {
        $us = new UserService();
        $combo = $us->getCombobox();

        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
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
        $form->addSubmit('send', 'Upravit akci');

        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formEditSubmitted(AppForm $form) {
        $values = $form->getValues();

        $values['start'] = $values['start']->format("Y-m-d");
        $values['end'] = $values['end']->format("Y-m-d");

        try {
            $id = $this->service->update($values);
        } catch (SoapFault $e) {
            if (preg_match("/EventFunction_LeaderMustBeAdult/", $e->getMessage())) {//dospělost vedoucího akce
                $this->flashMessage("Vedoucí akce musí být dosplělá osoba.", "fail");
            } elseif (preg_match("/EventFunction_AssistantMustBeAdult/", $e->getMessage())) { //dospělost zástupce
                $this->flashMessage("Zástupce musí být dosplělá osoba.", "fail");
            } else {
                throw $e;
            }
            $this->redirect("this");
        }


        if ($id) {
            $this->flashMessage("Akce byla upravena");
            $this->redirect("view", array("aid" => $values['aid']));
        } else {
            $this->flashMessage("Akci se nepodařilo upravit", "fail");
        }
        $this->redirect("this");
    }

    public function renderView($aid) {
        $this->template->data = $this->service->get($aid);
    }

    public function handleCancel($id) {
        if ($this->service->cancel($id)) {
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "fail");
        }
        $this->redirect("this");
    }

}

