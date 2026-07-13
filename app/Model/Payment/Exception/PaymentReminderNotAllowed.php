<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Exception;

use function sprintf;

final class PaymentReminderNotAllowed extends Exception
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Platbě "%s" nelze odeslat upomínku. Upomínky lze posílat jen po splatnosti a nejvýše jednou denně.', $name));
    }
}
