<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Payment\EmailType;
use Consistence\Enum\Enum;

final class PaymentEmailTypeType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return EmailType::class;
    }
}
