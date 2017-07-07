<?php

namespace Model\Logger\Repositories;


use Model\Logger\Log;

interface ILoggerRepository
{

    //public function findAllByUnit(int $unitId): array;

    public function save(Log $log): void;

}
