<?php

declare(strict_types=1);

namespace Model\Skautis;

use Cake\Chronos\ChronosDate;
use Model\Common\Member;
use Model\Common\Repositories\IMemberRepository;
use Model\Common\UnitId;
use Skautis\Skautis;

use function strcoll;
use function usort;

final class MemberRepository implements IMemberRepository
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return Member[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByUnit(UnitId $unitId, bool $includeSubunitMembers): array
    {
        $result = $this->skautis->org->PersonAll([
            'ID_Unit' => $unitId->toInt(),
            'OnlyDirectMember' => ! $includeSubunitMembers,
        ]);

        $members = [];

        foreach ($result as $member) {
            $members[] = new Member(
                $member->ID,
                $member->DisplayName,
                isset($member->Birthday) ? new ChronosDate($member->Birthday) : null,
            );
        }

        usort($members, fn (Member $first, Member $second) => strcoll($first->getName(), $second->getName()));

        return $members;
    }
}
