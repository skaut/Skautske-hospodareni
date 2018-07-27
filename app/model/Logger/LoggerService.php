<?php

declare(strict_types=1);

namespace Model;

use Model\DTO\Logger\LogFactory;
use Model\Logger\Log;
use Model\Logger\Log\Type;
use Model\Logger\Repositories\ILoggerRepository;
use function array_map;

class LoggerService
{
    /** @var ILoggerRepository */
    private $logs;

    public function __construct(ILoggerRepository $logs)
    {
        $this->logs = $logs;
    }

    public function log(int $unitId, int $userId, string $description, Type $type, ?int $typeId = null) : void
    {
        $this->logs->save(new Log($unitId, $userId, $description, $type, $typeId));
    }

    /**
     * @return \Model\DTO\Logger\Log[]
     */
    public function findAllByTypeId(Type $type, int $typeId) : array
    {
        return array_map(
            function (Log $log) {
                return LogFactory::create($log);
            },
            $this->logs->findAllByTypeId($type, $typeId)
        );
    }
}
