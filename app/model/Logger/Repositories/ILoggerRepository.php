<?php

namespace Model\Logger\Repositories;


use Model\Logger\Log;

interface ILoggerRepository
{

    public function findAllByObjectId(int $objectId): array;

    public function save(Log $log): void;

}
