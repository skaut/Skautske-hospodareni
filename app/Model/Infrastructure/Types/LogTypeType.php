<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Logger\Log\Type;
use Consistence\Enum\Enum;

final class LogTypeType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return Type::class;
    }
}
