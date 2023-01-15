<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Skautis\Factory\EventFactory;
use Skautis\Skautis;

use function is_object;

class EventListQueryHandler
{
    public function __construct(private Skautis $skautis, private EventFactory $eventFactory)
    {
    }

    /** @return array<int, Event> Events indexed by ID */
    public function __invoke(EventListQuery $query): array
    {
        $events = $this->skautis->event->eventGeneralAll([
            'IsRelation' => true,
            'ID_EventGeneralState' => $query->getState(),
            'Year' => $query->getYear(),
        ]);

        if (is_object($events)) {
            return [];
        }

        $result = [];

        foreach ($events as $event) {
            $event = $this->eventFactory->create($event);

            $result[$event->getId()->toInt()] = $event;
        }

        return $result;
    }
}
