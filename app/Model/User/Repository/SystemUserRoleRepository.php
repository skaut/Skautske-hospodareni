<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\User\Entity\SystemUserRole;
use App\Model\User\Enum\SystemRole;
use Doctrine\ORM\EntityManagerInterface;

use function ksort;

/** @extends AbstractRepository<SystemUserRole> */
class SystemUserRoleRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return SystemUserRole::class;
    }

    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['system_user_role']);
    }

    /** @return array<int, SystemRole[]> */
    public function findAllGroupedByUserId(): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        $rolesByUserId = [];
        foreach ($this->findBy([], ['userId' => 'ASC', 'role' => 'ASC']) as $assignment) {
            $rolesByUserId[$assignment->getUserId()][] = $assignment->getRole();
        }
        ksort($rolesByUserId);

        return $rolesByUserId;
    }

    /** @return SystemUserRole[] */
    public function findByUserId(int $userId): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        return $this->findBy(['userId' => $userId], ['role' => 'ASC']);
    }

    public function hasUserId(int $userId): bool
    {
        return $this->findByUserId($userId) !== [];
    }

    public function hasRole(int $userId, SystemRole $role): bool
    {
        if (! $this->isStorageAvailable()) {
            return false;
        }

        return $this->findOneBy(['userId' => $userId, 'role' => $role->value]) instanceof SystemUserRole;
    }
}
