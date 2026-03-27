<?php

declare(strict_types=1);

namespace App\Model\Logger\Repositories;

use App\Model\Logger\Log\Type;
use App\Model\Logger\LogEntry;

interface ILogEntryRepository
{
    /** @return LogEntry[] */
    public function findAllByTypeId(Type $type, int $typeId): array;

    public function save(LogEntry $log): void;
}
