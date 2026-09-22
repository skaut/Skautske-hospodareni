<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;

final class NonMemberParticipantService
{
    public function __construct(private IParticipantRepository $participants)
    {
    }

    public function get(int $personId): NonMemberParticipant
    {
        return $this->participants->getNonMemberParticipant($personId);
    }

    public function update(int $personId, NonMemberParticipant $participant): void
    {
        $this->participants->updateNonMemberParticipant($personId, $participant);
    }

    /**
     * @param  Participant[]    $participants
     * @return array<int, true>
     */
    public function findParticipantsWithMissingSex(array $participants): array
    {
        $participantsWithMissingSex = [];

        foreach ($participants as $participant) {
            if (! $participant->isNonMember()) {
                continue;
            }

            if ($this->get($participant->getPersonId())->getSex() === '') {
                $participantsWithMissingSex[$participant->getId()] = true;
            }
        }

        return $participantsWithMissingSex;
    }
}
