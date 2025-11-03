<?php

declare(strict_types=1);

namespace Repository;

use Entity\InvoiceSequenceActivityLog;

class InvoiceSequenceLogRepository extends ActivityLogRepository
{
    public function getActivityLogClass(): string
    {
        return InvoiceSequenceActivityLog::class;
    }
}
