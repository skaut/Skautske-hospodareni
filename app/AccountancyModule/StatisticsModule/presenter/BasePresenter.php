<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatisticsModule;

use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;

abstract class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /**
     * @var int
     * @persistent
     */
    public $unitId;

    protected Unit $unit;

    protected function startup() : void
    {
        parent::startup();

        $this->unitId = $this->unitService->getUnitId();
        $this->unit   = $this->queryBus->handle(new UnitQuery($this->unitId));

        $this->template->setParameters([
            'unit' => $this->unit,
        ]);
    }
}
