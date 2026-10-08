<?php

declare(strict_types=1);

namespace App\Model\User\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\User\DeviceInfo;
use App\Model\User\Entity\UserLogin;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserLoginManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return UserLogin::class;
    }

    public function start(
        int $userId,
        ?int $unitId,
        ?int $roleId,
        ?string $roleKey,
        DeviceInfo $device,
    ): UserLogin {
        $login = new UserLogin($userId, $unitId, $roleId, $roleKey, $device);
        $this->saveEntity($login);

        return $login;
    }

    public function touch(UserLogin $login, ?DateTimeImmutable $seenAt = null): void
    {
        $login->touch($seenAt);
        $this->saveEntity($login);
    }

    public function finish(UserLogin $login, ?DateTimeImmutable $loggedOutAt = null): void
    {
        $login->markLoggedOut($loggedOutAt);
        $this->saveEntity($login);
    }
}
