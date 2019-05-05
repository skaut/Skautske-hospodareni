<?php

declare(strict_types=1);

namespace Model\Common\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Model\Common\ReadModel\Queries\MemberNamesQuery;
use Model\Common\Repositories\IMemberRepository;

final class MemberNamesQueryHandler
{
    /** @var IMemberRepository */
    private $members;

    public function __construct(IMemberRepository $members)
    {
        $this->members = $members;
    }

    /**
     * @return array<int, string> Member ID => Member name
     */
    public function __invoke(MemberNamesQuery $query) : array
    {
        $minimalAge = $query->getMinimalAge();
        $today      = Date::today();

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
