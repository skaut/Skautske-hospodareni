<?php

declare(strict_types=1);

namespace Model\Common\ReadModel\QueryHandlers;

use Cake\Chronos\ChronosDate;
use Model\Common\ReadModel\Queries\MemberNamesQuery;
use Model\Common\Repositories\IMemberRepository;

final class MemberNamesQueryHandler
{
    public function __construct(private IMemberRepository $members)
    {
    }

    /** @return array<int, string> Member ID => Member name */
    public function __invoke(MemberNamesQuery $query): array
    {
        $minimalAge = $query->getMinimalAge();
        $today      = ChronosDate::today();

        $names = [];

        foreach ($this->members->findByUnit($query->getUnitId(), true) as $member) {
            $birthday = $member->getBirthday();

            if ($birthday === null || $birthday->diffInYears($today) < $minimalAge) {
                continue;
            }

            $names[$member->getId()] = $member->getName();
        }

        return $names;
    }
}
