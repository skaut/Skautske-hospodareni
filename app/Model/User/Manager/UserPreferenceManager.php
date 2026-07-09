<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\Entity\UserPreference;
use App\Model\User\Repository\UserPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserPreferenceManager extends AbstractManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private UserPreferenceRepository $repository,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return UserPreference::class;
    }

    public function saveHelpVisibility(int $userId, bool $showHelp): UserPreference
    {
        return $this->em->wrapInTransaction(function () use ($userId, $showHelp): UserPreference {
            $preference = $this->repository->findOneByUserId($userId) ?? new UserPreference($userId);
            $preference->setShowHelp($showHelp);
            $this->em->persist($preference);
            $this->em->flush();

            return $preference;
        });
    }

    public function savePreferences(
        int $userId,
        bool $showHelp,
        bool $extendSkautisLogin,
        bool $rememberSkautisRole,
    ): UserPreference {
        return $this->em->wrapInTransaction(function () use (
            $userId,
            $showHelp,
            $extendSkautisLogin,
            $rememberSkautisRole,
        ): UserPreference {
            $preference = $this->repository->findOneByUserId($userId) ?? new UserPreference($userId);
            $preference->updatePreferences($showHelp, $extendSkautisLogin, $rememberSkautisRole);
            $this->em->persist($preference);
            $this->em->flush();

            return $preference;
        });
    }

    public function saveRememberedSkautisRole(int $userId, int $roleId): UserPreference
    {
        return $this->em->wrapInTransaction(function () use ($userId, $roleId): UserPreference {
            $preference = $this->repository->findOneByUserId($userId) ?? new UserPreference($userId);
            $preference->rememberSkautisRole($roleId);
            $this->em->persist($preference);
            $this->em->flush();

            return $preference;
        });
    }

    public function clearRememberedSkautisRole(int $userId): UserPreference
    {
        return $this->em->wrapInTransaction(function () use ($userId): UserPreference {
            $preference = $this->repository->findOneByUserId($userId) ?? new UserPreference($userId);
            $preference->clearRememberedSkautisRole();
            $this->em->persist($preference);
            $this->em->flush();

            return $preference;
        });
    }
}
