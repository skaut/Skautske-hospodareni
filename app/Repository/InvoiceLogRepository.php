<?php

declare(strict_types=1);

namespace Repository;

use Entity\InvoiceActivityLog;

class InvoiceLogRepository extends ActivityLogRepository
{
    public function getActivityLogClass(): string
    {
        return InvoiceActivityLog::class;
    }
}
