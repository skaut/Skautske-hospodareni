<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\ObjectType;
use Consistence\Enum\Enum;

final class CashbookObjectTypeType extends AbstractEnumType
{
    public const NAME = 'cashbook_object_type';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return ObjectType::class;
    }
}
