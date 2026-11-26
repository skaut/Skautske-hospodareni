<?php

declare(strict_types=1);

namespace App\Model\DTO\Google;

use App\Model\Common\UnitId;
use App\Model\Google\OAuthId;
use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property OAuthId                $id
 * @property string                 $email
 * @property UnitId                 $unitId
 * @property DateTimeImmutable      $updatedAt
 * @property DateTimeImmutable|null $invalidSince
 */
class OAuth
{
    use SmartObject;

    public function __construct(
        private OAuthId $id,
        private string $email,
        private UnitId $unitId,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $invalidSince = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUnitId(): int
    {
        return $this->unitId->toInt();
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** Since when Google refuses the connection, null while it works. */
    public function getInvalidSince(): ?DateTimeImmutable
    {
        return $this->invalidSince;
    }

    public function isInvalid(): bool
    {
        return $this->invalidSince !== null;
    }
}
