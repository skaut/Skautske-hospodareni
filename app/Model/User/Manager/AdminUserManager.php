<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;

class AdminUserManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return AdminUser::class;
    }

    public function create(int $userId): AdminUser
    {
        return $this->em->wrapInTransaction(function () use ($userId): AdminUser {
            $adminUser = new AdminUser($userId);
            $this->em->persist($adminUser);
            $this->em->flush();

            return $adminUser;
        });
    }

    public function updateUserId(AdminUser $adminUser, int $userId): AdminUser
    {
        return $this->em->wrapInTransaction(function () use ($adminUser, $userId): AdminUser {
            $adminUser->renameToUserId($userId);
            $this->em->persist($adminUser);
            $this->em->flush();

            return $adminUser;
        });
    }

    public function delete(AdminUser $adminUser): void
    {
        $this->em->wrapInTransaction(function () use ($adminUser): void {
            $this->em->remove($adminUser);
            $this->em->flush();
        });
    }
}
