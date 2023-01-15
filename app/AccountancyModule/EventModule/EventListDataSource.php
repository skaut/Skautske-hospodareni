<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\Grids\DataSource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Event\EventListItem;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;

use function array_map;
use function assert;

final class EventListDataSource extends DataSource
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

    /** @return EventListItem[] */
    protected function loadData(): array
    {
        $events = $this->queryBus->handle(new EventListQuery($this->year, $this->state));

        return array_map(
            function (Event $event): EventListItem {
                return new EventListItem(
                    $event->getId()->toInt(),
                    $event->getDisplayName(),
                    $event->getStartDate(),
                    $event->getEndDate(),
                    $this->chitNumberPrefix($event),
                    $event->getState(),
                );
            },
            $events,
        );
    }

    private function chitNumberPrefix(Event $event): string|null
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));

        assert($cashbookId instanceof CashbookId);

        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix(PaymentMethod::CASH());
    }
}
