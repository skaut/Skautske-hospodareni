<?php

/**
 * @author sinacek
 * akce
 */
class Accountancy_ActionsPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        /**
         * @var Accountancy_ActionService
         */
        $this->service = new Accountancy_ActionService();
    }
    
    public function renderDefault(){
        $this->template->actions = $this->service->getByUser($this->user->getIdentity()->data["id"]);
    }

}


