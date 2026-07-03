<?php

declare(strict_types=1);

namespace App\Model\DTO\Google;

use App\Model\Common\UnitId;
use App\Model\Google\OAuthId;
use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property OAuthId           $id
 * @property string            $email
 * @property UnitId            $unitId
 * @property DateTimeImmutable $updatedAt
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
