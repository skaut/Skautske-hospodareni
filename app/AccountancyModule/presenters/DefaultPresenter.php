<?php

/**
 * @author sinacek
 */
class Accountancy_DefaultPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        $this->redirect("Action:");
    }
    
  
    
}
