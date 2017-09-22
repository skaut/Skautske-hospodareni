<?php

namespace Model;

use Model\DTO\Logger\LogFactory;
use Model\Logger\Log;
use Model\Logger\Log\Type;
use Model\Logger\Repositories\ILoggerRepository;

class LoggerService
{

    /** @var ILoggerRepository */
    private $logs;


    public function __construct(
        ILoggerRepository $lr
    )
    {
        $this->logs = $lr;
    }

    public function log(int $unitId, int $userId, string $description, Type $type, ?int $typeId = NULL): void
    {
        $this->logs->save(new Log($unitId, $userId, $description, $type, $typeId));
    }

    public function findAllByTypeId(Type $type, int $typeId): array
    {
        return array_map(function(Log $log) {
            return LogFactory::create($log);
        }, $this->logs->findAllByTypeId($type, $typeId));
    }


}
