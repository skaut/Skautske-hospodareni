<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Skautis\Factory\EventFactory;
use Skautis\Skautis;
use function array_combine;
use function array_map;
use function is_object;

class EventListQueryHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var EventFactory */
    private $eventFactory;

    public function __construct(Skautis $skautis, EventFactory $eventFactory)
    {
        $this->skautis      = $skautis;
        $this->eventFactory = $eventFactory;
    }

    /**
     * @return Event[]
     */
    public function handle(EventListQuery $query) : array
    {
        $events = $this->skautis->event->eventGeneralAll([
                'IsRelation' => true,
                'ID_EventGeneralState' => $query->getState(),
                'Year' => $query->getYear(),
        ]);

        if (is_object($events)) {
            return [];
        }
        $events = array_map([$this->eventFactory, 'create'], $events); //It changes ID to localIDs
        return array_combine(
            array_map(function (Event $u) : int {
                return $u->getId();
            }, $events),
            $events
        );
    }
}
