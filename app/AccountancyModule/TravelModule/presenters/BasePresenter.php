<?php

namespace App\AccountancyModule\TravelModule;

use Nette\NotImplementedException;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    protected $unit;

    protected function startup() : void
    {
        parent::startup();
        $this->template->unit = $this->unit = $this->unitService->getOficialUnit();
    }

    protected function editableOnly() : void
    {
        throw new NotImplementedException("todo");
    }

}
