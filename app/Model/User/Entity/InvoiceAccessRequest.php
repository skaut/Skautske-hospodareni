<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;

#[Entity(repositoryClass: \App\Model\User\Repository\InvoiceAccessRequestRepository::class)]
#[Table(name: 'invoice_access_request')]
class InvoiceAccessRequest extends AbstractIdEntity
{
    public const STATE_OPEN = 'open';
    public const STATE_APPROVED = 'approved';
    public const STATE_REJECTED = 'rejected';

    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'unit_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $unitId;

    #[Column(name: 'role_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $roleId;

    #[Column(name: 'display_name', type: Types::STRING, length: 255)]
    private string $displayName;

    #[Column(name: 'note', type: Types::TEXT)]
    private string $note;

    #[Column(name: 'state', type: Types::STRING, length: 20)]
    private string $state = self::STATE_OPEN;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[Column(name: 'resolved_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    public function __construct(
        int $userId,
        ?int $unitId,
        ?int $roleId,
        string $displayName,
        string $note,
        ?DateTimeImmutable $createdAt = null,
    ) {
        if ($userId < 1) {
            throw new InvalidArgumentException('Invoice access request user_id must be a positive integer.');
        }

        $this->userId = $userId;
        $this->unitId = $unitId;
        $this->roleId = $roleId;
        $this->displayName = $displayName;
        $this->note = $note;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function approve(): void
    {
        $this->state = self::STATE_APPROVED;
        $this->resolvedAt = new DateTimeImmutable();
    }

    public function reject(): void
    {
        $this->state = self::STATE_REJECTED;
        $this->resolvedAt = new DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }
}
