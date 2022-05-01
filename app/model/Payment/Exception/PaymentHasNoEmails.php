<?php

declare(strict_types=1);

namespace Model\Payment;

use Exception;

use function sprintf;

class PaymentHasNoEmails extends Exception
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Payment "%s" does not have any e-mail address filled', $name));
    }
}
