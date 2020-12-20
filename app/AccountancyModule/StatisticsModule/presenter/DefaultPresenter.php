<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatisticsModule;

use Model\StatisticsService;
use Model\Unit\ReadModel\Queries\UnitQuery;

use function date;

class DefaultPresenter extends BasePresenter
{
    private StatisticsService $statService;

    public function __construct(StatisticsService $statService)
    {
        parent::__construct();
        $this->statService = $statService;
    }

    public function renderDefault(?int $year = null): void
    {
        if ($year === null) {
            $year = (int) date('Y');
        }

        $unit     = $this->queryBus->handle(new UnitQuery($this->unitId->toInt()));
        $unitTree = $this->unitService->getTreeUnder($unit);
        $data     = $this->statService->getEventStatistics($unitTree, $year);

        $this->template->setParameters([
            'unit' => $unit,
            'unitTree' => $unitTree,
            'data' => $data,
        ]);
    }
}
