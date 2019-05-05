<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Common\Member;
use Model\Common\Repositories\IMemberRepository;
use Model\Common\UnitId;
use Model\Services\Language;
use Skautis\Skautis;
use function usort;

final class MemberRepository implements IMemberRepository
{
    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * @return Member[]
     */
    public function findByUnit(UnitId $unitId, bool $includeSubunitMembers) : array
    {
        $result = $this->skautis->org->PersonAll([
            'ID_Unit' => $unitId->toInt(),
            'OnlyDirectMember' => ! $includeSubunitMembers,
        ]);

        $members = [];

        foreach ($result as $member) {
            $members[] = new Member($member->ID, $member->DisplayName);
        }

        usort($members, function (Member $first, Member $second) : int {
            return Language::compare($first->getName(), $second->getName());
        });

        return $members;
    }
}
