<?php

declare(strict_types=1);

namespace App\Model\Cron\Entity;

use App\Model\Cron\CronInterval;
use App\Model\Cron\CronJobResult;
use App\Model\Cron\Repository\CronJobRepository;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * What the administration changed on one scheduler job and how its last run went.
 *
 * The job itself (callback and default schedule) stays in scheduler.neon, a missing row means
 * the job runs by its defaults. The last run is written by {@see CronJobRepository::recordStart()}
 * and {@see CronJobRepository::recordFinish()} with plain SQL, because a failed job may leave
 * the entity manager closed.
 */
#[Entity(repositoryClass: CronJobRepository::class)]
#[Table(name: 'cron_job')]
#[UniqueConstraint(name: 'cron_job_name_uniq', columns: ['name'])]
class CronJobState extends AbstractIdEntity
{
    public const NAME_MAX_LENGTH = 100;

    #[Column(name: 'name', type: Types::STRING, length: self::NAME_MAX_LENGTH)]
    private string $name;

    /** Cron expression chosen in the administration; null keeps the default from scheduler.neon. */
    #[Column(name: 'schedule', type: Types::STRING, length: 100, nullable: true)]
    private ?string $schedule = null;

    #[Column(name: 'paused', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $paused = false;

    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[Column(name: 'updated_by_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $updatedByName = null;

    #[Column(name: 'last_started_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastStartedAt = null;

    #[Column(name: 'last_finished_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastFinishedAt = null;

    #[Column(name: 'last_result', type: Types::STRING, length: 20, nullable: true)]
    private ?string $lastResult = null;

    #[Column(name: 'last_error', type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function pause(DateTimeImmutable $at, ?string $editorName): void
    {
        $this->paused = true;
        $this->touch($at, $editorName);
    }

    public function resume(DateTimeImmutable $at, ?string $editorName): void
    {
        $this->paused = false;
        $this->touch($at, $editorName);
    }

    /** @param CronInterval|null $schedule null returns the job to its default schedule */
    public function changeSchedule(?CronInterval $schedule, DateTimeImmutable $at, ?string $editorName): void
    {
        $this->schedule = $schedule?->value;
        $this->touch($at, $editorName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUpdatedByName(): ?string
    {
        return $this->updatedByName;
    }

    public function getLastStartedAt(): ?DateTimeImmutable
    {
        return $this->lastStartedAt;
    }

    public function getLastFinishedAt(): ?DateTimeImmutable
    {
        return $this->lastFinishedAt;
    }

    public function getLastResult(): ?CronJobResult
    {
        return $this->lastResult === null ? null : CronJobResult::tryFrom($this->lastResult);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function touch(DateTimeImmutable $at, ?string $editorName): void
    {
        $this->updatedAt = $at;
        $this->updatedByName = $editorName;
    }
}
