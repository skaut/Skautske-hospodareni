<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\Participant;

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
