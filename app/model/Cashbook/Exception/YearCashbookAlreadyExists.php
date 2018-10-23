<?php

declare(strict_types=1);

namespace Model\Cashbook\Exception;

use Model\Common\UnitId;
use function sprintf;

final class YearCashbookAlreadyExists extends \Exception
{
    public static function forYear(int $year, UnitId $unitId) : self
    {
        return new self(sprintf('Unit #%d already has cashbook for %d', $unitId->toInt(), $year));
    }
}
