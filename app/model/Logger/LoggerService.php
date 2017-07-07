<?php

namespace Model;

use Model\Logger\Log;
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

    public function log(int $unitId, int $userId, string $description, ?int $objectId = NULL): void
    {
        $this->logs->save(new Log($unitId, $userId, $description, $objectId));
    }

    public function findAllByObjectId($objectId): array
    {
        return $this->logs->findAllByObjectId($objectId);
    }


}
