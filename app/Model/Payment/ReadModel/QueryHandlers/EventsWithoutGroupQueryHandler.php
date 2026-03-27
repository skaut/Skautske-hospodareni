<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use App\Model\Payment\Group;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use App\Model\Payment\Repositories\IGroupRepository;

use function array_map;
use function assert;
use function in_array;

final class EventsWithoutGroupQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IGroupRepository $groups)
    {
    }

    /** @return Event[] */
    public function __invoke(EventsWithoutGroupQuery $query): array
    {
        $events = $this->queryBus->handle(new EventListQuery($query->getYear()));

        $eventWithGroupIds = $this->getEventWithGroupIds($events);
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
     *
     * @return int[]
     */
    private function getEventWithGroupIds(array $events): array
    {
        $skautisEntities = array_map(
            function (Event $event): SkautisEntity {
                return SkautisEntity::fromEventId($event->getId());
            },
            $events,
        );

        return array_map(
            function (Group $group): int {
                return $group->getObject()->getId();
            },
            $this->groups->findBySkautisEntities(...$skautisEntities),
        );
    }
}
