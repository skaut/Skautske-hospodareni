<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatisticsModule;

use Model\StatisticsService;
use Model\Unit\ReadModel\Queries\UnitQuery;

use function date;

class DefaultPresenter extends BasePresenter
{
    public function __construct(private StatisticsService $statService)
    {
        parent::__construct();
    }

    public function renderDefault(int|null $year = null): void
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
