<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use RuntimeException;

use function sprintf;

final class InvoiceAlreadySent extends RuntimeException
{
    public static function withNumber(string $number): self
    {
        return new self(sprintf('Faktura "%s" už byla odeslána. Pro další odeslání použijte akci "Odeslat znovu".', $number));
    }
}
