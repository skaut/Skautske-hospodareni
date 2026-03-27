<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use Consistence\Enum\Enum;

/** @method string getValue() */
final class EmailType extends Enum
{
    public const INVOICE_INFO = 'invoice_info';

    public const INVOICE_REMINDER = 'invoice_reminder';

    public function toString(): string
    {
        return $this->getValue();
    }
}
