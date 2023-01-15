<?php

declare(strict_types=1);

namespace Model;

use DateTimeImmutable;
use Model\DTO\Logger\LogFactory;
use Model\Logger\Log\Type;
use Model\Logger\LogEntry;
use Model\Logger\Repositories\ILogEntryRepository;

use function array_map;

class LoggerService
{
    public function __construct(private ILogEntryRepository $logs)
    {
    }

    public function log(int $unitId, int $userId, string $description, Type $type, int|null $typeId = null): void
    {
        $this->logs->save(new LogEntry($unitId, $userId, $description, $type, $typeId, new DateTimeImmutable()));
    }

    /** @return \Model\DTO\Logger\LogEntry[] */
    public function findAllByTypeId(Type $type, int $typeId): array
    {
        return array_map(
            function (LogEntry $log) {
                return LogFactory::create($log);
            },
            $this->logs->findAllByTypeId($type, $typeId),
        );
    }
}
