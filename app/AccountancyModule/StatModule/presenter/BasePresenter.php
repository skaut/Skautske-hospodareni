<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatModule;

use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /**
     * @var int
     * @persistent
     */
    public $unitId;

    /** @var Unit */
    protected $unit;

    protected function startup() : void
    {
        parent::startup();

        $this->unitId = $this->unitService->getUnitId();
//        $this->unitId = $this->getParameter('unitId', null);
//        if ($this->unitId === null) {
//            $this->unitId = $this->unitService->getUnitId();
//        } else {
//            $this->unitId = (int) $this->unitId; // Parameters aren't auto-casted to int
//        }

        $this->unit = $this->queryBus->handle(new UnitQuery($this->unitId));

        $this->template->setParameters([
            'unit' => $this->unit,
        ]);
    }
}
