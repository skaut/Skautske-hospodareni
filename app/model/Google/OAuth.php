<?php

declare(strict_types=1);

namespace Model\Google;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Common\Aggregate;
use Model\Common\UnitId;

/**
 * @ORM\Entity()
 * @ORM\Table(name="google_oauth", uniqueConstraints={@ORM\UniqueConstraint(name="unitid_email", columns={"unit_id", "email"})} )
 */
class OAuth extends Aggregate
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="ouath_id")
     */
    private OAuthId $id;

    /**
     * @ORM\Column(type="unit_id")
     */
    private UnitId $unitId;

    /**
     * @ORM\Column(type="string")
     */
    private string $email;

    /**
     * @ORM\Column(type="string")
     */
    private string $token;

    /** @ORM\Column(type="datetime_immutable") */
    private DateTimeImmutable $updatedAt;

    private function __construct(OAuthId $id, UnitId $unitId, string $code, string $email, DateTimeImmutable $updatedAt)
    {
        $this->id        = $id;
        $this->unitId    = $unitId;
        $this->token     = $code;
        $this->email     = $email;
        $this->updatedAt = $updatedAt;
    }

    public static function create(UnitId $unitId, string $code, string $email) : self
    {
        return new self(OAuthId::generate(), $unitId, $code, $email, new DateTimeImmutable());
    }

    public function setToken(string $token) : void
    {
        $this->token     = $token;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId() : OAuthId
    {
        return $this->id;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getToken() : string
    {
        return $this->token;
    }

    public function getUpdatedAt() : DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
