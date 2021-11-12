<?php

declare(strict_types=1);

namespace Model\Cashbook\Subscribers;

use Model\Cashbook\Commands\Cashbook\ClearCashbook;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\Events\Events\EventWasCanceled;

final class EventWasCanceledSubscriber
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function __invoke(EventWasCanceled $event): void
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getEventId()));

        $this->commandBus->handle(new ClearCashbook($cashbookId));
    }
}
