<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\CashbookType;
use Consistence\Enum\Enum;

final class CashbookTypeType extends AbstractEnumType
{
    public const NAME = 'cashbook_type';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return CashbookType::class;
    }
}
