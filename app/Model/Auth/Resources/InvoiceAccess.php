<?php

declare(strict_types=1);

namespace App\Model\Auth\Resources;

use Nette\StaticClass;

final class InvoiceAccess
{
    use StaticClass;

    public const ACCESS = [self::class, 'INVOICE_ACCESS'];
}
