<?php

namespace App\AccountancyModule\TravelModule;

use Nette\NotImplementedException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
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
