<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Participants;

use App\AccountancyModule\Components\Participants\ParticipantList;
use Model\DTO\Participant\ParticipatingPerson;

interface IParticipantListFactory
{
    /** @param ParticipatingPerson[] $currentParticipants */
    public function create(
        int $aid,
        array $currentParticipants,
        bool $isAllowDaysUpdate,
        bool $isAllowRepayment,
        bool $isAllowIsAccount,
        bool $isAllowParticipantUpdate,
        bool $isAllowParticipantDelete,
        bool $isOnlineLogin,
        bool $isAllowShowUnits = true,
        string $title = 'Seznam účastníků',
    ): ParticipantList;
}
