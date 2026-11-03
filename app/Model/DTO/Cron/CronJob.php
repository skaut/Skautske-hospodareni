<?php

declare(strict_types=1);

namespace App\Model\DTO\Cron;

use App\Model\Cron\CronInterval;
use App\Model\Cron\CronJobResult;
use DateTimeImmutable;

/** A scheduler job as the administration and the settings show it. */
final class CronJob
{
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $description,
        public readonly string $schedule,
        public readonly string $defaultSchedule,
        public readonly bool $paused,
        /** Null while the job is paused. */
        public readonly ?DateTimeImmutable $nextRunAt,
        /** The latest planned run that did not start, typically because the external server stopped calling. */
        public readonly ?DateTimeImmutable $missedRunAt,
        public readonly ?DateTimeImmutable $lastStartedAt,
        public readonly ?DateTimeImmutable $lastFinishedAt,
        public readonly ?CronJobResult $lastResult,
        public readonly ?string $lastError,
        public readonly ?DateTimeImmutable $updatedAt,
        public readonly ?string $updatedByName,
    ) {
    }

    public function getInterval(): ?CronInterval
    {
        return CronInterval::tryFrom($this->schedule);
    }

    /** Falls back to the cron expression when it is not one of the offered intervals. */
    public function getIntervalLabel(): string
    {
        return $this->getInterval()?->getLabel() ?? $this->schedule;
    }

    public function hasDefaultSchedule(): bool
    {
        return $this->schedule === $this->defaultSchedule;
    }

    public function getLastDurationSeconds(): ?int
    {
        if ($this->lastStartedAt === null || $this->lastFinishedAt === null || $this->isRunning()) {
            return null;
        }

        return $this->lastFinishedAt->getTimestamp() - $this->lastStartedAt->getTimestamp();
    }

    /** Also true when the hosting killed the last run before it could record its end. */
    public function isRunning(): bool
    {
        return $this->lastStartedAt !== null
            && ($this->lastFinishedAt === null || $this->lastFinishedAt < $this->lastStartedAt);
    }
}
