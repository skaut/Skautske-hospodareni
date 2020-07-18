<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\BankAccount\AccountNumber;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_bank_account")
 */
class BankAccount
{
    private const FIO_BANK_CODE = '2010';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="integer") */
    private int $unitId;

    /** @ORM\Column(type="string") */
    private string $name;

    /** @ORM\Embedded(class=AccountNumber::class) */
    private AccountNumber $number;

    /** @ORM\Column(type="string", nullable=true) */
    private ?string $token = null;

    /** @ORM\Column(type="datetime_immutable") */
    private DateTimeImmutable $createdAt;

    /** @ORM\Column(type="boolean") */
    private bool $allowedForSubunits = false;

    public function __construct(
        int $unitId,
        string $name,
        AccountNumber $number,
        ?string $token,
        DateTimeImmutable $createdAt,
        IUnitResolver $unitResolver
    ) {
        $this->unitId = $unitResolver->getOfficialUnitId($unitId);
        $this->update($name, $number, $token);
        $this->createdAt = $createdAt;
    }

    public function allowForSubunits() : void
    {
        $this->allowedForSubunits = true;
    }

    public function disallowForSubunits() : void
    {
        $this->allowedForSubunits = false;
    }

    public function update(string $name, AccountNumber $number, ?string $token) : void
    {
        $this->name   = $name;
        $this->number = $number;
        $this->token  = $number->getBankCode() !== self::FIO_BANK_CODE || $token === '' ? null : $token;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getNumber() : AccountNumber
    {
        return $this->number;
    }

    public function getToken() : ?string
    {
        return $this->token;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits() : bool
    {
        return $this->allowedForSubunits;
    }
}
