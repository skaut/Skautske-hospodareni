<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\Participant;

final class EventParticipantListQueryHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    /** @return Participant[] */
    public function __invoke(EventParticipantListQuery $query): array
    {
        return $this->participants->findByEvent($query->getEventId());
    }
}
