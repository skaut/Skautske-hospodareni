<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * One cron run of the task queue that did something, for the statistics in the administration.
 * Empty runs every minute are not stored. A run without the finish time was cut off by the hosting.
 * The rows are written with plain SQL in {@see TaskQueueRepository}.
 */
#[Entity]
#[Table(name: 'task_queue_run')]
#[Index(name: 'task_queue_run_started_at_idx', columns: ['started_at'])]
class TaskQueueRun extends AbstractIdEntity
{
    #[Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $startedAt;

    #[Column(name: 'finished_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    /** Tasks the run took from the queue. */
    #[Column(name: 'taken', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $taken = 0;

    #[Column(name: 'done', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $done = 0;

    #[Column(name: 'skipped', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $skipped = 0;

    #[Column(name: 'failed', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $failed = 0;

    #[Column(name: 'uncertain', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $uncertain = 0;

    #[Column(name: 'postponed', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $postponed = 0;

    /** Tasks of earlier runs that died: returned to the queue, or marked uncertain. */
    #[Column(name: 'recovered', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $recovered = 0;

    /** Why the run stopped taking tasks, see TaskQueueRunStopReason. */
    #[Column(name: 'stop_reason', type: Types::STRING, length: 20, nullable: true)]
    private ?string $stopReason = null;

    /** Tasks still waiting when the run ended. */
    #[Column(name: 'pending_after', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $pendingAfter = null;

    public function __construct(DateTimeImmutable $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getTaken(): int
    {
        return $this->taken;
    }

    public function getDone(): int
    {
        return $this->done;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getUncertain(): int
    {
        return $this->uncertain;
    }

    public function getPostponed(): int
    {
        return $this->postponed;
    }

    public function getRecovered(): int
    {
        return $this->recovered;
    }

    public function getStopReason(): ?string
    {
        return $this->stopReason;
    }

    public function getPendingAfter(): ?int
    {
        return $this->pendingAfter;
    }
}
