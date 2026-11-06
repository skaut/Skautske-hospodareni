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
use function preg_match;

/**
 * One row per skautIS login, kept for the general overview of the usage statistics.
 *
 * Nothing that leads to a person: instead of the user only a keyed hash (see KeyedHasher) that lets the
 * overview count distinct users, no unit, no role, no IP address and no raw User-Agent.
 */
#[Entity(repositoryClass: \App\Model\User\Repository\UserLoginRepository::class)]
#[Table(name: 'user_login')]
#[Index(name: 'user_login_logged_in_idx', columns: ['logged_in_at'])]
#[Index(name: 'user_login_user_idx', columns: ['user_hash', 'logged_in_at'])]
class UserLogin extends AbstractIdEntity
{
    public const END_REASON_LOGOUT = 'logout';

    #[Column(name: 'user_hash', type: Types::STRING, length: 64, options: ['fixed' => true])]
    private string $userHash;

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

    /** Whether the pages showed dark, as the browser reported once per login. Null until it reports. */
    #[Column(name: 'dark_mode', type: Types::BOOLEAN, nullable: true)]
    private ?bool $darkMode = null;

    public function __construct(string $userHash, DeviceInfo $device, ?DateTimeImmutable $loggedInAt = null)
    {
        if (preg_match('~^[0-9a-f]{64}$~', $userHash) !== 1) {
            throw new InvalidArgumentException('User login user_hash must be a SHA-256 hash in hex.');
        }

        $this->userHash = $userHash;
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

    public function reportTheme(bool $darkMode): void
    {
        $this->darkMode = $darkMode;
    }

    public function getUserHash(): string
    {
        return $this->userHash;
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

    public function isDarkMode(): ?bool
    {
        return $this->darkMode;
    }

    /** Time between the first and the last real request, not the token lifetime. */
    public function getActiveSecondsSoFar(): int
    {
        return max(0, $this->lastSeenAt->getTimestamp() - $this->loggedInAt->getTimestamp());
    }
}
