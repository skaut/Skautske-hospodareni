<?php

namespace Model\DTO\Logger;

use Model\DTO\Logger\Log as LogDTO;
use Model\Logger\Log;

class LogFactory
{

    public static function create(Log $log): LogDTO
    {
        return new LogDTO(
            $log->getUnitId(),
            $log->getDate(),
            $log->getUserId(),
            $log->getDescription(),
            $log->getObjectId()
        );
    }

}
