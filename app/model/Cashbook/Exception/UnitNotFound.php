<?php

declare(strict_types=1);

namespace Model\Cashbook\Exception;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\UnitId;
use function sprintf;

final class UnitNotFound extends \Exception
{
    public static function withId(UnitId $id) : self
    {
        return new self(sprintf('Unit #%d not found', $id->toInt()));
    }

    public static function forCashbook(CashbookId $cashbookId, ?\Throwable $previous) : self
    {
        return new self(sprintf('Unit for cashbook #%s not found', $cashbookId), 0, $previous);
    }
}
