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
}
