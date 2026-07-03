<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use Exception;

use function sprintf;

class InvoiceHasNoEmails extends Exception
{
    public static function withNumber(string $number): self
    {
        return new self(sprintf('Faktura "%s" nemá vyplněný žádný e-mail', $number));
    }
}
