<?php

declare(strict_types=1);

namespace Model\Cashbook\Exception;

use Model\Common\UnitId;
use function sprintf;

final class UnitNotFound extends \Exception
{
    public static function withId(UnitId $id) : self
    {
        return new self(sprintf('Unit #%d not found', $id->toInt()));
    }
}
