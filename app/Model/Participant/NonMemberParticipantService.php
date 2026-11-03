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

    /** @throws PersonAccessNotAllowed */
    public function get(int $personId): NonMemberParticipant
    {
        return $this->participants->getNonMemberParticipant($personId);
    }

    /**
     * @throws PersonAccessNotAllowed
     * @throws PersonAddressChangeNotAllowed
     */
    public function update(int $personId, NonMemberParticipant $participant): void
    {
        $this->participants->updateNonMemberParticipant($personId, $participant);
    }

    /**
     * Osobní údaje nečlenů ze seznamu účastníků.
     *
     * @param  Participant[]                         $participants
     * @return array<int, NonMemberParticipant|null> ID účastníka => osobní údaje, null když k nim uživatel nemá ve skautISu přístup
     */
    public function findNonMembers(array $participants): array
    {
        $nonMembers = [];

        foreach ($participants as $participant) {
            if (! $participant->isNonMember()) {
                continue;
            }

            try {
                $nonMembers[$participant->getId()] = $this->get($participant->getPersonId());
            } catch (PersonAccessNotAllowed) {
                $nonMembers[$participant->getId()] = null;
            }
        }

        return $nonMembers;
    }
}
