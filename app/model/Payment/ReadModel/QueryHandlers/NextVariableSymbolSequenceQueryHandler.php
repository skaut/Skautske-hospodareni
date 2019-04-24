<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Payment\Group;
use Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\VariableSymbol;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use Nette\Utils\Strings;
use function array_filter;
use function assert;
use function count;
use function ltrim;
use function str_replace;

class NextVariableSymbolSequenceQueryHandler
{
    private const UNIT_PART_LENGTH = 3;

    /** @var IGroupRepository */
    private $groups;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(IGroupRepository $groups, QueryBus $queryBus)
    {
        $this->groups   = $groups;
        $this->queryBus = $queryBus;
    }

    public function handle(NextVariableSymbolSequenceQuery $query) : ?VariableSymbol
    {
        $now                = $query->getNow();
        $groupIncrementPart = $this->getGroupIncrement($query->getUnitId(), $now);

        if (Strings::length($groupIncrementPart) > 2) {
            return null;
        }

        $unitPart = $this->getLastDigitsOfUnitNumber($query->getUnitId());

        return new VariableSymbol($now->format('y') . $unitPart . $groupIncrementPart . '001');
    }

    private function getGroupIncrement(int $unitId, \DateTimeImmutable $now) : string
    {
        $currentYear = $now->format('Y');

        $groups = $this->groups->findByUnits([$unitId], false);
        $groups = array_filter(
            $groups,
            function (Group $group) use ($currentYear) : bool {
                return $group->getCreatedAt() !== null && $group->getCreatedAt()->format('Y') === $currentYear;
            }
        );

        return Strings::padLeft((string) (count($groups) + 1), 2, '0');
    }

    private function getLastDigitsOfUnitNumber(int $unitId) : string
    {
        $unit = $this->queryBus->handle(new UnitQuery($unitId));

        assert($unit instanceof Unit);

        $number = $unit->getShortRegistrationNumber();
        $number = ltrim($number, '0');
        $number = str_replace('-', '', $number);

        $length = Strings::length($number);

        if ($length > self::UNIT_PART_LENGTH) {
            return Strings::substring($number, $length - self::UNIT_PART_LENGTH);
        }

        return Strings::padLeft($number, self::UNIT_PART_LENGTH, '0');
    }
}
