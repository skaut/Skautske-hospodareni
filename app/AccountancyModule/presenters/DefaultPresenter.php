<?php

namespace App\AccountancyModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class DefaultPresenter extends BasePresenter
{

    /**
     * pouze přesměrovává na jiný presenter
     */
    function startup()
    {
        parent::startup();
        $this->redirect("Event:Default:");
    }

}
