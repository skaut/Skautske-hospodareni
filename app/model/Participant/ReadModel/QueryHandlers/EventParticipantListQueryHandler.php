<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\EventEntity;

final class EventParticipantListQueryHandler
{
    /** @var EventEntity */
    private $eventService;

    public function __construct(EventEntity $eventEntity)
    {
        $this->eventService = $eventEntity;
    }

    /**
     * @return Participant[]
     */
    public function __invoke(EventParticipantListQuery $query) : array
    {
        return $this->eventService->getParticipants()->getAll($query->getEventId()->toInt());
    }
}
