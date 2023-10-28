<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Grids\DataSource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Education\EducationListItem;
use Model\Event\Education;
use Model\Event\ReadModel\Queries\EducationListQuery;

use function array_map;
use function assert;

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

    /** @return EducationListItem[] */
    protected function loadData(): array
    {
        $educationEvents = $this->queryBus->handle(new EducationListQuery($this->year));

        return array_map(
            function (Education $education): EducationListItem {
                return new EducationListItem(
                    $education->getId()->toInt(),
                    $education->getDisplayName(),
                    $education->getStartDate(),
                    $education->getEndDate(),
                    $this->chitNumberPrefix($education),
                );
            },
            $educationEvents,
        );
    }

    private function chitNumberPrefix(Education $education): string|null
    {
        if ($education->startDate === null) {
            return null;
        }

        $cashbookId = $this->queryBus->handle(new EducationCashbookIdQuery($education->getId(), $education->startDate->year));

        assert($cashbookId instanceof CashbookId);

        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix(PaymentMethod::CASH());
    }
}
