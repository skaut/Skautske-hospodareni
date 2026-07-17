<?php

declare(strict_types=1);

namespace App\Components\Event;

use App\Components\Grids\DataSource;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Event\EventListItem;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use LogicException;

use function array_map;

final class EventListDataSource extends DataSource
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

    private function chitNumberPrefix(Event $event): ?string
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));

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
