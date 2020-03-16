<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Participants;

use App\AccountancyModule\Components\Participants\ParticipantList;
use Model\DTO\Participant\Participant;
use Model\EventEntity;

interface IParticipantListFactory
{
    /**
     * @param Participant[] $currentParticipants
     */
    public function create(
        int $aid,
        EventEntity $eventService,
        array $currentParticipants,
        bool $isAllowRepayment,
        bool $isAllowIsAccount,
        bool $isAllowParticipantUpdate,
        bool $isAllowParticipantDelete
    ) : ParticipantList;
}
