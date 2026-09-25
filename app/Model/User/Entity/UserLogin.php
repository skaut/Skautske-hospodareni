<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\User\DeviceInfo;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;

use function max;

/**
 * One row per skautIS login, kept for usage statistics.
 *
 * No IP address and no raw User-Agent: every question the administration asks
 * is answered by the parsed triple, so the identifying form is never stored.
 */
#[Entity(repositoryClass: \App\Model\User\Repository\UserLoginRepository::class)]
#[Table(name: 'user_login')]
#[Index(name: 'user_login_logged_in_idx', columns: ['logged_in_at'])]
#[Index(name: 'user_login_user_idx', columns: ['user_id', 'logged_in_at'])]
#[Index(name: 'user_login_unit_idx', columns: ['unit_id', 'logged_in_at'])]
class UserLogin extends AbstractIdEntity
{
    public const END_REASON_LOGOUT = 'logout';

    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'unit_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $unitId;

    #[Column(name: 'role_id', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $roleId;

    #[Column(name: 'role_key', type: Types::STRING, length: 64, nullable: true)]
    private ?string $roleKey;

    #[Column(name: 'logged_in_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $loggedInAt;

    /** Last request that was not a session keep-alive ping. */
    #[Column(name: 'last_seen_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $lastSeenAt;

    #[Column(name: 'logged_out_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $loggedOutAt = null;

    /** Only ever "logout" — a null on a stale row means the session expired. */
    #[Column(name: 'end_reason', type: Types::STRING, length: 16, nullable: true)]
    private ?string $endReason = null;

    #[Column(name: 'device_type', type: Types::STRING, length: 16)]
    private string $deviceType;

    #[Column(name: 'browser', type: Types::STRING, length: 32)]
    private string $browser;

    #[Column(name: 'browser_version', type: Types::STRING, length: 16, nullable: true)]
    private ?string $browserVersion;

    #[Column(name: 'platform', type: Types::STRING, length: 32)]
    private string $platform;

    public function __construct(
        int $userId,
        ?int $unitId,
        ?int $roleId,
        ?string $roleKey,
        DeviceInfo $device,
        ?DateTimeImmutable $loggedInAt = null,
    ) {
        if ($userId < 1) {
            throw new InvalidArgumentException('User login user_id must be a positive integer.');
        }

        $this->userId = $userId;
        $this->unitId = $unitId !== null && $unitId > 0 ? $unitId : null;
        $this->roleId = $roleId !== null && $roleId > 0 ? $roleId : null;
        $this->roleKey = $roleKey !== null && $roleKey !== '' ? $roleKey : null;
        $this->loggedInAt = $loggedInAt ?? new DateTimeImmutable();
        $this->lastSeenAt = $this->loggedInAt;
        $this->deviceType = $device->getType();
        $this->browser = $device->getBrowser();
        $this->browserVersion = $device->getBrowserVersion();
        $this->platform = $device->getPlatform();
    }

    public function touch(?DateTimeImmutable $seenAt = null): void
    {
        $seenAt ??= new DateTimeImmutable();

        if ($seenAt < $this->lastSeenAt) {
            return;
        }

        $this->lastSeenAt = $seenAt;
    }

    public function markLoggedOut(?DateTimeImmutable $loggedOutAt = null): void
    {
        $loggedOutAt ??= new DateTimeImmutable();

        $this->loggedOutAt = $loggedOutAt;
        $this->endReason = self::END_REASON_LOGOUT;
        $this->touch($loggedOutAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getRoleKey(): ?string
    {
        return $this->roleKey;
    }

    public function getLoggedInAt(): DateTimeImmutable
    {
        return $this->loggedInAt;
    }

    public function getLastSeenAt(): DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function getLoggedOutAt(): ?DateTimeImmutable
    {
        return $this->loggedOutAt;
    }

    public function getEndReason(): ?string
    {
        return $this->endReason;
    }

    public function wasEndedByLogout(): bool
    {
        return $this->endReason === self::END_REASON_LOGOUT;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    /** Time between the first and the last real request, not the token lifetime. */
    public function getActiveSecondsSoFar(): int
    {
        return max(0, $this->lastSeenAt->getTimestamp() - $this->loggedInAt->getTimestamp());
    }
}
