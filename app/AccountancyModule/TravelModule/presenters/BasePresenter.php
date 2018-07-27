<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use Nette\NotImplementedException;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var \stdClass */
    protected $unit;

    protected function startup() : void
    {
        parent::startup();
        $this->template->unit = $this->unit = $this->unitService->getOfficialUnit();
    }

    protected function editableOnly() : void
    {
        throw new NotImplementedException('todo');
    }
}
