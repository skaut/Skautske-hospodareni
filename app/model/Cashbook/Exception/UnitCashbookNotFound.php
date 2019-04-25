<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Exception;
use Model\Common\UnitId;
use function sprintf;

final class UnitCashbookNotFound extends Exception
{
    public static function withId(int $cashbookId, UnitId $unitId) : self
    {
        return new self(sprintf('Unit #%d doesn\'t have cashbook #%d', $unitId->toInt(), $cashbookId));
    }
}
