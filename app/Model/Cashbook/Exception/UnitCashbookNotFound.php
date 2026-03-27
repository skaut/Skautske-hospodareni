<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use App\Model\Common\UnitId;
use Exception;

use function sprintf;

final class UnitCashbookNotFound extends Exception
{
    public static function withId(int $cashbookId, UnitId $unitId): self
    {
        return new self(sprintf('Unit #%d doesn\'t have cashbook #%d', $unitId->toInt(), $cashbookId));
    }
}
