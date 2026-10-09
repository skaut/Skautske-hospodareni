<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Log\Tracy;

use Throwable;
use Tracy\ILogger;

final class CompositeLogger implements ILogger
{
    /** @var list<ILogger> */
    private readonly array $loggers;

    public function __construct(ILogger ...$loggers)
    {
        $this->loggers = array_values($loggers);
    }

    public function log(mixed $value, string $level = self::INFO): void
    {
        $exception = null;

        foreach ($this->loggers as $logger) {
            try {
                $logger->log($value, $level);
            } catch (Throwable $caughtException) {
                $exception ??= $caughtException;
            }
        }

        if ($exception !== null) {
            throw $exception;
        }
    }
}
