<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use Model\Unit\Unit;
use Nette\NotImplementedException;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var Unit */
    protected $officialUnit;

    protected function startup() : void
    {
        parent::startup();
        $this->officialUnit = $this->unitService->getOfficialUnit();
        $this->template->setParameters([
            'unit' => $this->officialUnit,
        ]);
    }

    protected function editableOnly() : void
    {
        throw new NotImplementedException('todo');
    }
}
