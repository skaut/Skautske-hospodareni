<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\Repositories\IEventRepository;

class EventQueryHandler
{
    public function __construct(private IEventRepository $eventRepository)
    {
    }

    public function __invoke(EventQuery $query): Event
    {
        return $this->eventRepository->find($query->getEventId());
    }
}
