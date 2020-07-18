<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\Participant;

final class EventParticipantListQueryHandler
{
    private IParticipantRepository $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    /**
     * @return Participant[]
     */
    public function __invoke(EventParticipantListQuery $query) : array
    {
        return $this->participants->findByEvent($query->getEventId());
    }
}
