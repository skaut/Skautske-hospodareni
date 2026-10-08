<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\User\Entity\UserLogin;
use Doctrine\ORM\EntityManagerInterface;

class UserLoginRepository extends AbstractRepository
{
    private ?bool $storageAvailable = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return UserLogin::class;
    }

    /**
     * Lets tracking stay dormant between a deploy and the migration instead of
     * breaking every login in that window.
     */
    public function isStorageAvailable(): bool
    {
        return $this->storageAvailable ??= $this->getEntityManager()
            ->getConnection()
            ->createSchemaManager()
            ->tablesExist(['user_login']);
    }

    public function findById(int $id): ?UserLogin
    {
        if (! $this->isStorageAvailable()) {
            return null;
        }

        /** @var UserLogin|null $login */
        $login = $this->find($id);

        return $login;
    }
}
