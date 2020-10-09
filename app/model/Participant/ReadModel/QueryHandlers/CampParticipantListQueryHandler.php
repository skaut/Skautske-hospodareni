<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\Participant;

final class CampParticipantListQueryHandler
{
    private IParticipantRepository $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    /**
     * @return Participant[]
     */
    public function __invoke(CampParticipantListQuery $query) : array
    {
        return $this->participants->findByCamp($query->getCampId());
    }
}
