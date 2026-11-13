<?php

declare(strict_types=1);

namespace App\Model\TaskQueue\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\TaskQueue\Repository\TaskQueueRepository;
use App\Model\TaskQueue\TaskItemState;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * One queued task, e.g. one e-mail. See {@see TaskItemState} for its life cycle.
 *
 * The dedup key stays filled only while the task is active. Its unique index keeps the same task
 * (e.g. the same e-mail of the same payment) from waiting in the queue twice. The mapping only
 * describes the schema; {@see TaskQueueRepository} works with the rows.
 */
#[Entity]
#[Table(name: 'task_queue_item')]
#[UniqueConstraint(name: 'task_queue_item_dedup_key_uniq', columns: ['dedup_key'])]
#[Index(name: 'task_queue_item_state_idx', columns: ['state', 'available_at'])]
#[Index(name: 'task_queue_item_job_state_idx', columns: ['job_id', 'state'])]
class TaskItem extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: TaskJob::class)]
    #[JoinColumn(name: 'job_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TaskJob $job;

    #[Column(name: 'state', type: Types::STRING, length: 20, enumType: TaskItemState::class)]
    private TaskItemState $state = TaskItemState::PENDING;

    /** @var array<string, mixed> */
    #[Column(name: 'payload', type: Types::JSON)]
    private array $payload;

    /** What the task is about in words, e.g. the payment name. */
    #[Column(name: 'label', type: Types::STRING, length: 255)]
    private string $label;

    #[Column(name: 'dedup_key', type: Types::STRING, length: 191, nullable: true)]
    private ?string $dedupKey;

    #[Column(name: 'attempts', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $attempts = 0;

    /** Runs that died holding the task before its irreversible part; unlike attempts, postponing does not count. */
    #[Column(name: 'abandoned', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $abandoned = 0;

    #[Column(name: 'available_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $availableAt;

    #[Column(name: 'claimed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $claimedAt = null;

    #[Column(name: 'claim_token', type: Types::STRING, length: 32, nullable: true)]
    private ?string $claimToken = null;

    #[Column(name: 'finished_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    /** Why the task was skipped, failed or is uncertain, in words for people. */
    #[Column(name: 'message', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    /** @param array<string, mixed> $payload */
    public function __construct(TaskJob $job, array $payload, string $label, ?string $dedupKey, DateTimeImmutable $availableAt)
    {
        $this->job = $job;
        $this->payload = $payload;
        $this->label = $label;
        $this->dedupKey = $dedupKey;
        $this->availableAt = $availableAt;
    }

    public function getJob(): TaskJob
    {
        return $this->job;
    }

    public function getState(): TaskItemState
    {
        return $this->state;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDedupKey(): ?string
    {
        return $this->dedupKey;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getAvailableAt(): DateTimeImmutable
    {
        return $this->availableAt;
    }

    public function getClaimedAt(): ?DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function getClaimToken(): ?string
    {
        return $this->claimToken;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
