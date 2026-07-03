<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Subscribers;

use App\Model\Cashbook\Commands\Cashbook\ClearCashbook;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Events\Events\EventWasCanceled;

final class EventWasCanceledSubscriber
{
    public function __construct(private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function __invoke(EventWasCanceled $event): void
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getEventId()));

        $this->commandBus->handle(new ClearCashbook($cashbookId));
    }
}
