<?php

/**
 * @author sinacek
 * akce
 */
class Accountancy_ActionPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        /** @var ActionService */
        $this->service = new ActionService();
    }
    
    public function actionList(){
        $list = $this->service->getMyActions();
        //dump($list);
        if(empty($list)){
            $this->redirect("create");
        }
        $this->template->list = $list;
    }
    
    public function renderCreate() {
        
    }
    
    function createComponentFormCreate($name) {
        $form = new AppForm($this, $name);
        $form->addText("name", "Název akce");
        $form->addDatePicker("start", "Od");
        $form->addDatePicker("end", "Do");
        $form->addSubmit('send', 'Založit akci');
        $form->onSuccess[] = array($this, $name . 'Submitted');
        return $form;
    }

    function formCreateSubmitted(AppForm $form) {
        $values = $form->getValues();
        $id = $this->service->create($values['name'], $values['start']->format("Y-m-d"), $values['end']->format("Y-m-d"));
        if($id){
            $this->flashMessage("Akce byla založena");
            $this->redirect("list");
        } else {
            $this->flashMessage("Akci se nepodařilo založit", "fail");
        }
        $this->redirect("this");
    }
    
    public function renderView($aid) {
        $this->template->a = $this->service->getDetail($aid);
    }
    
    public function handleCancel($id){
        if($this->service->cancel($id)){
            $this->flashMessage("Akce byla zrušena");
        } else {
            $this->flashMessage("Akci se nepodařilo zrušit", "fail");
        }
        $this->redirect("this");
    }

}


