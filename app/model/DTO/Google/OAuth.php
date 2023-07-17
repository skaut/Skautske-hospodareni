<?php

declare(strict_types=1);

namespace Model\DTO\Google;

use DateTimeImmutable;
use Model\Common\UnitId;
use Model\Google\OAuthId;
use Nette\SmartObject;

/**
 * @property-read OAuthId $id
 * @property-read string $email
 * @property-read UnitId $unitId
 * @property-read DateTimeImmutable $updatedAt
 */
class OAuth
{
    use SmartObject;

    public function __construct(private OAuthId $id, private string $email, private UnitId $unitId, private DateTimeImmutable $updatedAt)
    {
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
}
