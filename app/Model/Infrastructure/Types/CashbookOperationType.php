<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Operation;
use Consistence\Enum\Enum;

final class CashbookOperationType extends AbstractEnumType
{
    public const NAME = 'cashbook_operation';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return Operation::class;
    }
}
