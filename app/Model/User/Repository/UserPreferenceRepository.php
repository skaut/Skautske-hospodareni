<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\User\Entity\UserPreference;
use Doctrine\ORM\EntityManagerInterface;

class UserPreferenceRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return UserPreference::class;
    }

    public function findOneByUserId(int $userId): ?UserPreference
    {
        /** @var UserPreference|null $preference */
        $preference = $this->findOneBy(['userId' => $userId]);

        return $preference;
    }
}
