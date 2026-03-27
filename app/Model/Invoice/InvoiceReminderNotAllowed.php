<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use RuntimeException;

use function sprintf;

final class InvoiceReminderNotAllowed extends RuntimeException
{
    public static function withNumber(string $number): self
    {
        return new self(sprintf('Upomínku lze odeslat jen k nezaplacené faktuře po splatnosti, která už byla dříve odeslána. Faktura "%s" tuto podmínku nesplňuje.', $number));
    }
}
