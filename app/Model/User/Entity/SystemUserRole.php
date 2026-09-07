<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\User\Enum\SystemRole;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

#[Entity(repositoryClass: \App\Model\User\Repository\SystemUserRoleRepository::class)]
#[Table(name: 'system_user_role')]
#[UniqueConstraint(name: 'system_user_role_user_role_unique', columns: ['user_id', 'role'])]
class SystemUserRole extends AbstractIdEntity
{
    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'role', type: Types::STRING, length: 32)]
    private string $role;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(int $userId, SystemRole $role, ?DateTimeImmutable $createdAt = null)
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('System role user_id must be a positive integer.');
        }

        $this->userId = $userId;
        $this->role = $role->value;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRole(): SystemRole
    {
        return SystemRole::from($this->role);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
