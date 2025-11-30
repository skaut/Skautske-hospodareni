<?php

declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Entity\Embeddable\AccountNumber;
use Model\Payment\IUnitResolver;

#[Entity]
#[Table(name: 'pa_bank_account')]
class BankAccount
{
    public const FIO_BANK_CODE = '2010';

    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Column(type: 'integer')]
    private int $id;

    #[Column(type: 'integer')]
    private int $unitId;

    #[Column(type: 'string')]
    private string $name;

    #[Embedded(class: AccountNumber::class)]
    private AccountNumber $number;

    #[Column(type: 'string', nullable: true)]
    private ?string $token = null;

    #[Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[Column(type: 'boolean')]
    private bool $allowedForSubunits = false;

    public function __construct(
        int $unitId,
        string $name,
        AccountNumber $number,
        ?string $token,
        DateTimeImmutable $createdAt,
        IUnitResolver $unitResolver,
    ) {
        $this->unitId = $unitResolver->getOfficialUnitId($unitId);
        $this->update($name, $number, $token);
        $this->createdAt = $createdAt;
    }

    public function allowForSubunits(): void
    {
        $this->allowedForSubunits = true;
    }

    public function disallowForSubunits(): void
    {
        $this->allowedForSubunits = false;
    }

    public function update(string $name, AccountNumber $number, ?string $token): void
    {
        $this->name = $name;
        $this->number = $number;
        $this->token = $number->getBankCode() !== self::FIO_BANK_CODE || $token === '' ? null : $token;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNumber(): AccountNumber
    {
        return $this->number;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits(): bool
    {
        return $this->allowedForSubunits;
    }
}
