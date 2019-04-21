<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;
use function array_map;
use function assert;
use function in_array;

final class EventsWithoutGroupQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    /** @var IGroupRepository */
    private $groups;

    public function __construct(QueryBus $queryBus, IGroupRepository $groups)
    {
        $this->queryBus = $queryBus;
        $this->groups   = $groups;
    }

    /**
     * @return Event[]
     */
    public function __invoke(EventsWithoutGroupQuery $query) : array
    {
        $events = $this->queryBus->handle(new EventListQuery($query->getYear()));

        $eventWithGroupIds  = $this->getEventWithGroupIds($events);
        $eventsWithoutGroup = [];

        foreach ($events as $event) {
            assert($event instanceof Event);

            $eventId = $event->getId()->toInt();

            if (in_array($eventId, $eventWithGroupIds, true)) {
                continue;
            }

            $eventsWithoutGroup[$eventId] = $event;
        }

        return $eventsWithoutGroup;
    }

    /**
     * @param Event[] $events
     * @return int[]
     */
    private function getEventWithGroupIds(array $events) : array
    {
        $skautisEntities = array_map(
            function (Event $event) : SkautisEntity {
                return SkautisEntity::fromEventId($event->getId());
            },
            $events
        );

        return array_map(
            function (Group $group) : int {
                return $group->getObject()->getId();
            },
            $this->groups->findBySkautisEntities(...$skautisEntities)
        );
    }
}
