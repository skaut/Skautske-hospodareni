<?php

declare(strict_types=1);

namespace Model\Logger\Repositories;

use Model\Logger\Log\Type;
use Model\Logger\LogEntry;

interface ILogEntryRepository
{
    /**
     * @return LogEntry[]
     */
    public function findAllByTypeId(Type $type, int $typeId) : array;

    public function save(LogEntry $log) : void;
}
