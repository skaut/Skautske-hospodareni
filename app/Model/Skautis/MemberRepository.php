<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Common\Member;
use App\Model\Common\Repositories\IMemberRepository;
use App\Model\Common\UnitId;
use App\Utils\CzechStringComparator;
use Cake\Chronos\ChronosDate;
use Skautis\Skautis;

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

        usort($members, fn (Member $first, Member $second) => CzechStringComparator::compare($first->getName(), $second->getName()));

        return $members;
    }
}
