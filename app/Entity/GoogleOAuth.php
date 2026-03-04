<?php

declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Model\Common\Aggregate;
use Model\Common\UnitId;
use Model\Google\OAuthId;

#[Entity]
#[Table(name: 'google_oauth')]
#[UniqueConstraint(name: 'unitid_email', columns: ['unit_id', 'email'])]
class GoogleOAuth extends Aggregate
{
    #[Id]
    #[Column(type: 'oauth_id')]
    private OAuthId $id;

    #[Column(type: 'unit_id')]
    private UnitId $unitId;

    #[Column(type: 'string')]
    private string $email;

    #[Column(type: 'string')]
    private string $token;

    #[Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(OAuthId $id, UnitId $unitId, string $code, string $email, DateTimeImmutable $updatedAt)
    {
        $this->id = $id;
        $this->unitId = $unitId;
        $this->token = $code;
        $this->email = $email;
        $this->updatedAt = $updatedAt;
    }

    public static function create(UnitId $unitId, string $code, string $email): self
    {
        return new self(OAuthId::generate(), $unitId, $code, $email, new DateTimeImmutable());
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): OAuthId
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
