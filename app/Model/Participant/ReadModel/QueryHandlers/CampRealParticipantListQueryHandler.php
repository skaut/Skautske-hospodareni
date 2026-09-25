<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampRealParticipantListQuery;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\Participant;

final class CampRealParticipantListQueryHandler
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    /** @return Participant[] */
    public function __invoke(CampRealParticipantListQuery $query): array
    {
        return $this->participants->findRealByCamp($query->getCampId());
    }
}
