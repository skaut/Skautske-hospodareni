<?php

declare(strict_types=1);

namespace Model\Logger\Repositories;

use Model\Logger\Log;
use Model\Logger\Log\Type;

interface ILoggerRepository
{
    /**
     * @return Log[]
     */
    public function findAllByTypeId(Type $type, int $typeId) : array;

    public function save(Log $log) : void;
}
