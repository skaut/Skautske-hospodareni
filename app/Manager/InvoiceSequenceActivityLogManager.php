<?php

declare(strict_types=1);

namespace Manager;

use Entity\InvoiceSequenceActivityLog;

use function dump;

class InvoiceSequenceActivityLogManager extends ActivityLogManager
{
    public function getActivityLogClass(): string
    {
        dump(InvoiceSequenceActivityLog::class);

        return InvoiceSequenceActivityLog::class;
    }
}
