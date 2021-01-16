<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\Repositories\IEventRepository;

final class EventCashbookIdQueryHandler
{
    private IEventRepository $eventRepository;

    public function __construct(IEventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function __invoke(EventCashbookIdQuery $query): CashbookId
    {
        return $this->eventRepository->findBySkautisId($query->getEventId())->getCashbookId();
    }
}
