<?php

namespace AccountancyModule;

/**
 * @author sinacek
 */
class DefaultPresenter extends \BasePresenter {

    /**
     * pouze přesměrovává na jiný presenter
     */
    function startup() {
        parent::startup();
        $this->redirect("Event:Default:");
    }

}
