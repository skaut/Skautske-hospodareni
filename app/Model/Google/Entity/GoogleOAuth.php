<?php

declare(strict_types=1);

namespace App\Model\Google\Entity;

use App\Model\Common\Aggregate;
use App\Model\Common\UnitId;
use App\Model\Google\OAuthId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

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

    #[Column(type: 'string', length: 255)]
    private string $email;

    #[Column(type: 'string', length: 255)]
    private string $token;

    #[Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * Since when Google refuses the token (e.g. the owner revoked the access), null while it works.
     * Written by {@see \App\Model\Google\OAuthValidity}, connecting the account again clears it.
     */
    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $invalidSince = null;

    /** What Google answered, for the administrators only. */
    #[Column(type: 'string', length: 255, nullable: true)]
    private ?string $invalidReason = null;

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

    /** A new token comes from connecting the account again, so Google accepts it. */
    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->updatedAt = new DateTimeImmutable();
        $this->invalidSince = null;
        $this->invalidReason = null;
    }

    public function isInvalid(): bool
    {
        return $this->invalidSince !== null;
    }

    public function getInvalidSince(): ?DateTimeImmutable
    {
        return $this->invalidSince;
    }

    public function getInvalidReason(): ?string
    {
        return $this->invalidReason;
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
