<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatisticsModule;

use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;

abstract class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    //@TODO: unify $unitIdInt and $unitId
    /**
     * @var int
     * @persistent
     */
    public int $unitIdInt;

    protected Unit $unit;

    protected function startup() : void
    {
        parent::startup();

        $this->unitIdInt = $this->unitService->getUnitId();
        $this->unit      = $this->queryBus->handle(new UnitQuery($this->unitIdInt));

        $this->template->setParameters([
            'unit' => $this->unit,
        ]);
    }
}
