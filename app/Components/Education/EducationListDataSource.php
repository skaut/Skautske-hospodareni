<?php

declare(strict_types=1);

namespace App\Components\Education;

use App\Components\Grids\DataSource;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Education\EducationListItem;
use App\Model\Event\Education;
use App\Model\Event\ReadModel\Queries\EducationListQuery;

use function array_map;
use function assert;

final class EducationListDataSource extends DataSource
{
    private ?int $year = null;

    public function __construct(private QueryBus $queryBus)
    {
    }

    public function filterByYear(?int $year): self
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

    private function chitNumberPrefix(Education $education): ?string
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
