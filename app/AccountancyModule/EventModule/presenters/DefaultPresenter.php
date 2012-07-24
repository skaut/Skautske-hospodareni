<?php

/**
 * @author sinacek
 */
class Accountancy_Event_DefaultPresenter extends Accountancy_Event_BasePresenter  {

    /**
     * pouze přesměrovává na jiný presenter
     */
    function startup() {
        parent::startup();
        $this->redirect("Event:");
    }
}
