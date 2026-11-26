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
 * A batch of queued tasks started together, e.g. reminders of one payment group.
 *
 * The scope (scope_type + scope_id) says whose batch it is. Everyone with access to that scope sees
 * the same progress, regardless of who started the batch. The mapping only describes the schema;
 * the queue reads and changes the rows with plain SQL in {@see TaskQueueRepository}, because each
 * state change has to be an atomic conditional update.
 */
#[Entity]
#[Table(name: 'task_queue_job')]
#[Index(name: 'task_queue_job_scope_idx', columns: ['scope_type', 'scope_id'])]
class TaskJob extends AbstractIdEntity
{
    #[Column(name: 'type', type: Types::STRING, length: 50)]
    private string $type;

    #[Column(name: 'scope_type', type: Types::STRING, length: 50)]
    private string $scopeType;

    #[Column(name: 'scope_id', type: Types::INTEGER)]
    private int $scopeId;

    #[Column(name: 'label', type: Types::STRING, length: 255)]
    private string $label;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    /** Null means the batch was started automatically. */
    #[Column(name: 'created_by_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $createdByName;

    /** Someone hid the finished batch with problems from the overview. */
    #[Column(name: 'acknowledged_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $acknowledgedAt = null;

    #[Column(name: 'acknowledged_by_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $acknowledgedByName = null;

    public function __construct(string $type, string $scopeType, int $scopeId, string $label, DateTimeImmutable $createdAt, ?string $createdByName)
    {
        $this->type = $type;
        $this->scopeType = $scopeType;
        $this->scopeId = $scopeId;
        $this->label = $label;
        $this->createdAt = $createdAt;
        $this->createdByName = $createdByName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedByName(): ?string
    {
        return $this->createdByName;
    }

    public function getAcknowledgedAt(): ?DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    public function getAcknowledgedByName(): ?string
    {
        return $this->acknowledgedByName;
    }
}
