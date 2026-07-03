<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

#[Entity(repositoryClass: \App\Model\User\Repository\InvoiceAccessUserRepository::class)]
#[Table(name: 'invoice_access_user')]
#[UniqueConstraint(name: 'invoice_access_user_user_id_unique', columns: ['user_id'])]
class InvoiceAccessUser extends AbstractIdEntity
{
    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(int $userId, ?DateTimeImmutable $createdAt = null)
    {
        $this->renameToUserId($userId);
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function renameToUserId(int $userId): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('Invoice access user_id must be a positive integer.');
        }

        $this->userId = $userId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
