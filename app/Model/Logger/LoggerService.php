<?php

declare(strict_types=1);

namespace App\Model\Logger;

use App\Model\DTO\Logger\LogFactory;
use App\Model\Logger\Log\Type;
use App\Model\Logger\Repositories\ILogEntryRepository;
use DateTimeImmutable;

use function array_map;

class LoggerService
{
    public function __construct(private ILogEntryRepository $logs)
    {
    }

    public function log(int $unitId, int $userId, string $description, Type $type, ?int $typeId = null): void
    {
        $this->logs->save(new LogEntry($unitId, $userId, $description, $type, $typeId, new DateTimeImmutable()));
    }

    /** @return \App\Model\DTO\Logger\LogEntry[] */
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
