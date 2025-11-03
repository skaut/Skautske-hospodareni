<?php

declare(strict_types=1);

namespace Repository;

use Entity\AuditLog;

/** @extends AbstractRepository<AuditLog> */
class AuditLogRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return AuditLog::class;
    }
}
