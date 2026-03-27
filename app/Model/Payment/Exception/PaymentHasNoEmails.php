<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Exception;

use function sprintf;

class PaymentHasNoEmails extends Exception
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Platba "%s" nemá vyplněný žádný e-mail', $name));
    }
}
