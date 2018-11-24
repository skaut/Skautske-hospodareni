<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\ParticipantListQuery;
use Model\Participant\Participant;
use Model\Participant\Repositories\IParticipantRepository;

final class ParticipantListQueryHandler
{
    /** @var IParticipantRepository */
    private $participants;

    public function __construct(IParticipantRepository $participants)
    {
        $this->participants = $participants;
    }

    /**
     * @return Participant[]
     */
    public function __invoke(ParticipantListQuery $query) : array
    {
        return $this->participants->findByEvent($query->getEvent());
    }
}
