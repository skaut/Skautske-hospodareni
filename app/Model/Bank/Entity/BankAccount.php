<?php

declare(strict_types=1);

namespace App\Model\Bank\Entity;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Payment\IUnitResolver;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;

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

    #[Column(type: 'string', length: 20, nullable: true)]
    private ?string $transactionSource = null;

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
        ?BankTransactionSource $transactionSource = null,
    ) {
        $this->unitId = $unitResolver->getOfficialUnitId($unitId);
        $this->update($name, $number, $token, $transactionSource);
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

    public function update(string $name, AccountNumber $number, ?string $token, ?BankTransactionSource $transactionSource = null): void
    {
        $resolvedSource = $transactionSource ?? $this->resolveDefaultTransactionSource($number);

        if ($resolvedSource->value === BankTransactionSource::FIO->value && $number->getBankCode() !== self::FIO_BANK_CODE) {
            throw new InvalidArgumentException('FIO zdroj lze použít pouze pro účty vedené u FIO banky.');
        }

        $this->name = $name;
        $this->number = $number;
        $this->transactionSource = $resolvedSource->value;
        $this->token = $resolvedSource->value !== BankTransactionSource::FIO->value || $token === '' ? null : $token;
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

    public function getTransactionSource(): BankTransactionSource
    {
        return $this->transactionSource !== null
            ? BankTransactionSource::from($this->transactionSource)
            : $this->resolveDefaultTransactionSource($this->number);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAllowedForSubunits(): bool
    {
        return $this->allowedForSubunits;
    }

    private function resolveDefaultTransactionSource(AccountNumber $number): BankTransactionSource
    {
        return $number->getBankCode() === self::FIO_BANK_CODE
            ? BankTransactionSource::FIO
            : BankTransactionSource::GPC;
    }
}
