<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Grids\DataSource;
use Model\Common\Services\QueryBus;
use Model\Event\Education;
use Model\Event\ReadModel\Queries\EducationListQuery;

final class EducationListDataSource extends DataSource
{
    private int|null $year = null;

    public function __construct(private QueryBus $queryBus)
    {
    }

    public function filterByYear(int|null $year): self
    {
        $this->year = $year;

        return $this;
    }

    /** @return Education[] */
    protected function loadData(): array
    {
        return $this->queryBus->handle(new EducationListQuery($this->year));
    }
}
