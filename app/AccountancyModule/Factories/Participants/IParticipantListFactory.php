<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Participants;

use App\AccountancyModule\Components\Participants\ParticipantList;
use Model\DTO\Participant\Participant;

interface IParticipantListFactory
{
    /**
     * @param Participant[] $currentParticipants
     */
    public function create(
        int $aid,
        array $currentParticipants,
        bool $isAllowRepayment,
        bool $isAllowIsAccount,
        bool $isAllowParticipantUpdate,
        bool $isAllowParticipantDelete
    ): ParticipantList;
}
