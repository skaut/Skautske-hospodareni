<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\Participant;

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
