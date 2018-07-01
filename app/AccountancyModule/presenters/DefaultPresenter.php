<?php

namespace App\AccountancyModule;

class DefaultPresenter extends BasePresenter
{

    /**
     * pouze přesměrovává na jiný presenter
     */
    protected function startup() : void
    {
        parent::startup();
        $this->redirect("Event:Default:");
    }

}
