<?php

/**
 * @author sinacek
 */
class Accountancy_DefaultPresenter extends Accountancy_BasePresenter  {

    /**
     * pouze přesměrovává na jiný presenter
     */
    function startup() {
        parent::startup();
        $this->redirect("Event:Default:");
    }
}
