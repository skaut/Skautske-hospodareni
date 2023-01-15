<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\Participant;

final class CampParticipantListQueryHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    /** @return Participant[] */
    public function __invoke(CampParticipantListQuery $query): array
    {
        return $this->participants->findByCamp($query->getCampId());
    }
}
