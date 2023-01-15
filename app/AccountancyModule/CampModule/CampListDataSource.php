<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\Grids\DataSource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Camp\CampListItem;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampListQuery;

use function array_map;
use function assert;

final class CampListDataSource extends DataSource
{
    private int|null $year = null;

    private string|null $state = null;

    public function __construct(private QueryBus $queryBus)
    {
    }

    public function filterByYear(int|null $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function filterByState(string|null $state): self
    {
        $this->state = $state;

        return $this;
    }

    /** @return CampListItem[] */
    protected function loadData(): array
    {
        $camps = $this->queryBus->handle(new CampListQuery($this->year, $this->state));

        return array_map(
            function (Camp $camp): CampListItem {
                return new CampListItem(
                    $camp->getId()->toInt(),
                    $camp->getDisplayName(),
                    $camp->getStartDate(),
                    $camp->getEndDate(),
                    $camp->getLocation(),
                    $this->chitNumberPrefix($camp),
                    $camp->getState(),
                );
            },
            $camps,
        );
    }

    private function chitNumberPrefix(Camp $camp): string|null
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($camp->getId()));

        assert($cashbookId instanceof CashbookId);

        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix(PaymentMethod::CASH());
    }
}
