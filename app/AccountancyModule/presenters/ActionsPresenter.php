<?php

/**
 * @author sinacek
 * akce
 */
class Accountancy_ActionPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        /**
         * @var ActionService
         */
        $this->service = new ActionService();
    }
    
    protected function createComponentUFinder() {
		$ufc = new UserFinderControl();
		return $ufc;
	}
    
    public function renderList() {
        $this->template->list = $this->service->getMyActions();
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
        } else {
            $this->flashMessage("Akci se nepodařilo založit", "fail");
        }
        $this->redirect("this"); //@todo přesměrovat na lepsi stranku
    }
    
    public function renderView($id) {
        $this->template->detail = $this->service->getDetail($id);
    }

}


