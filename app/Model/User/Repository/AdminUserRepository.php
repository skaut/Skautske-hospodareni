<?php

declare(strict_types=1);

namespace App\Model\User\Repository;
use App\Model\Infrastructure\Repository\AbstractRepository;

use Doctrine\ORM\EntityManagerInterface;
use App\Model\User\Entity\AdminUser;

class AdminUserRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return AdminUser::class;
    }

    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['admin_user']);
    }

    /** @return AdminUser[] */
    public function findAllOrderedByUserId(): array
    {
        if (! $this->isStorageAvailable()) {
            return [];
        }

        return $this->findBy([], ['userId' => 'ASC']);
    }

    public function findOneByUserId(int $userId): ?AdminUser
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        /** @var AdminUser|null $adminUser */
        $adminUser = $this->findOneBy(['userId' => $userId]);

        return $adminUser;
    }

    public function hasUserId(int $userId): bool
    {
        return $this->findOneByUserId($userId) instanceof AdminUser;
    }
}
