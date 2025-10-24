<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EventState;
use Model\Event\ReadModel\Queries\EventStatsQuery;
use Skautis\Skautis;

use function is_object;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class EventStatsQueryHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return array<string, int>  */
    public function __invoke(EventStatsQuery $query): array
    {
        $events = $this->skautis->event->eventGeneralAll([
            'IsRelation' => true,
            'Year' => $query->getYear(),
        ]);

        $counters = [];
        foreach (EventState::toArray() as $event) {
            $counters[$event] = 0;
        }

        if (is_object($events)) {
            return $counters;
        }

        foreach ($events as $event) {
            $state = $event->ID_EventGeneralState;
            if (! isset($counters[$state])) {
                continue;
            }

            $counters[$state]++;
        }

        return $counters;
    }
}
