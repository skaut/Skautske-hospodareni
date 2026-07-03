<?php

declare(strict_types=1);

namespace App\Model\DTO\Logger;

use App\Model\DTO\Logger\LogEntry as LogDTO;
use App\Model\Logger\LogEntry;

class LogFactory
{
    public static function create(LogEntry $log): LogDTO
    {
        return new LogDTO(
            $log->getUnitId(),
            $log->getDate(),
            $log->getUserId(),
            $log->getDescription(),
            $log->getType(),
            $log->getTypeId(),
        );
    }
}
