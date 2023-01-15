<?php

declare(strict_types=1);

namespace Model\Participant\ReadModel\QueryHandlers;

use Model\Common\Repositories\IMemberRepository;
use Model\Participant\ReadModel\Queries\PotentialParticipantListQuery;

final class PotentialParticipantListQueryHandler
{
    public function __construct(private IMemberRepository $members)
    {
    }

    /** @return array<int, string> member ID => member name */
    public function __invoke(PotentialParticipantListQuery $query): array
    {
        $all = $this->members->findByUnit($query->getUnitId(), ! $query->directMembersOnly());

        $check = [];

        foreach ($query->getCurrentParticipants() as $p) {
            $check[$p->getPersonId()] = true;
        }

        $potentialParticipants = [];

        foreach ($all as $member) {
            if (isset($check[$member->getId()])) {
                continue;
            }

            $potentialParticipants[$member->getId()] = $member->getName();
        }

        return $potentialParticipants;
    }
}
