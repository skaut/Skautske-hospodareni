<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\Repositories\IEventRepository;

final class EventCashbookIdQueryHandler
{
    public function __construct(private IEventRepository $eventRepository)
    {
    }

    public function __invoke(EventCashbookIdQuery $query): CashbookId
    {
        return $this->eventRepository->findBySkautisId($query->getEventId())->getCashbookId();
    }
}
