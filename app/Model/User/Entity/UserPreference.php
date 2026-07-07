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

#[Entity(repositoryClass: \App\Model\User\Repository\UserPreferenceRepository::class)]
#[Table(name: 'user_preference')]
#[UniqueConstraint(name: 'user_preference_user_id_unique', columns: ['user_id'])]
class UserPreference extends AbstractIdEntity
{
    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'show_help', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $showHelp;

    #[Column(name: 'extend_skautis_login', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $extendSkautisLogin;

    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        int $userId,
        bool $showHelp = true,
        bool $extendSkautisLogin = false,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        if ($userId < 1) {
            throw new InvalidArgumentException('User preference user_id must be a positive integer.');
        }

        $this->userId = $userId;
        $this->showHelp = $showHelp;
        $this->extendSkautisLogin = $extendSkautisLogin;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function setShowHelp(bool $showHelp): void
    {
        $this->showHelp = $showHelp;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setExtendSkautisLogin(bool $extendSkautisLogin): void
    {
        $this->extendSkautisLogin = $extendSkautisLogin;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePreferences(bool $showHelp, bool $extendSkautisLogin): void
    {
        $this->showHelp = $showHelp;
        $this->extendSkautisLogin = $extendSkautisLogin;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function shouldShowHelp(): bool
    {
        return $this->showHelp;
    }

    public function shouldExtendSkautisLogin(): bool
    {
        return $this->extendSkautisLogin;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
