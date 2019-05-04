<?php

declare(strict_types=1);

namespace Model\Participant\ReadModel\QueryHandlers;

use Model\Common\Repositories\IMemberRepository;
use Model\Participant\ReadModel\Queries\PotentialParticipantListQuery;
use Model\DTO\Participant\Participant;
use function natcasesort;

final class PotentialParticipantListQueryHandler
{
    /** @var IMemberRepository */
    private $members;

    public function __construct(IMemberRepository $members)
    {
        $this->members = $members;
    }

    /**
     * @return array<int, string> member ID => member name
     */
    public function __invoke(PotentialParticipantListQuery $query) : array
    {
        $participants = $query->getCurrentParticipants();
        $all          = $this->members->findByUnit($query->getUnitId(), ! $query->directMembersOnly());
        $ret = [];

        if (empty($participants)) {
            foreach ($all as $people) {
                $ret[$people->ID] = $people->DisplayName;
            }
        } else { //odstranení již označených
            $check = [];
            /** @var Participant $p */
            foreach ($participants as $p) {
                $check[$p->getId()] = true;
            }
            foreach ($all as $member) {
                if (array_key_exists($member->getId(), $check)) {
                    continue;
                }

                $ret[$member->getId()] = $member->getName();
            }
        }
        natcasesort($ret);

        return $ret;
    }
}
