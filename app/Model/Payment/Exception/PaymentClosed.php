<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Exception;

use function sprintf;

class PaymentClosed extends Exception
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Platba "%s" je již uzavřena.', $name));
    }
}
