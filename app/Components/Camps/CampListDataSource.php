<?php

declare(strict_types=1);

namespace App\Components\Camps;

use App\Components\Grids\DataSource;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Camp\CampListItem;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\Event\Camp;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use LogicException;

use function array_map;

final class CampListDataSource extends DataSource
{
    private ?int $year = null;

    private ?string $state = null;

    public function __construct(private QueryBus $queryBus)
    {
    }

    public function filterByYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function filterByState(?string $state): self
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

    private function chitNumberPrefix(Camp $camp): ?string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($camp->getId()));

        if (! $cashbookId instanceof CashbookId) {
            throw new LogicException('Assertion failed.');
        }
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        if (! $cashbook instanceof Cashbook) {
            throw new LogicException('Assertion failed.');
        }

        return $cashbook->getChitNumberPrefix(PaymentMethod::CASH());
    }
}
