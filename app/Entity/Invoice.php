<?php

declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Enum\InvoiceState;
use Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use Model\Payment\Payment\Transaction;
use Model\Payment\VariableSymbol;
use Nette\Utils\ArrayHash;

#[Entity]
#[Table(name: 'invoice')]
class Invoice extends AbstractIdEntity
{
    #[Column(type: Types::INTEGER, nullable: true)]
    private int $invoiceId;

    #[Column(type: Types::STRING, length: 255)]
    private string $issuedBy;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $dueDate;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $dateOfIssue;

    #[Column(type: 'string_enum', length: 20)]
    private string $state = InvoiceState::ISSUED;

    #[Column(type: 'variable_symbol', length: 10, nullable: false)]
    private VariableSymbol $variableSymbol;

    #[Embedded(class: Transaction::class, columnPrefix: false)]
    #[Nullable]
    private Transaction|null $transaction = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private DateTimeImmutable|null $closedAt = null;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private string|null $closedByUsername = null;

    /** @var ?Collection&iterable<InvoiceItem> */
    #[OneToMany(mappedBy: 'item', targetEntity: InvoiceItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection|null $items = null;

    #[Column(type: Types::STRING, length: 255)]
    private string $name;

    #[Column(type: Types::STRING, length: 255)]
    private string $address;

    #[Column(type: Types::STRING, length: 20, nullable: true)]
    private string|null $companyNumber;

    #[Column(type: Types::STRING, length: 20, nullable: true)]
    private string|null $vatNumber = null;

    #[Column(type: Types::BOOLEAN)]
    private bool $vatPayer = false;

    public function __construct(
        string $issuedBy,
        DateTimeImmutable $dueDate,
        DateTimeImmutable $dateOfIssue,
        string $name,
        string $address,
        string $companyNumber,
        string $vatNumber,
        bool $vatPayer,
        VariableSymbol|null $variableSymbol = null,
    ) {
        $this->issuedBy       = $issuedBy;
        $this->dueDate        = $dueDate;
        $this->dateOfIssue    = $dateOfIssue;
        $this->name           = $name;
        $this->address        = $address;
        $this->companyNumber  = $companyNumber;
        $this->vatNumber      = $vatNumber;
        $this->vatPayer       = $vatPayer;
        $this->variableSymbol = $variableSymbol ?? new VariableSymbol('111');
    }

    public static function formForm(ArrayHash $values): self
    {
        return new self(
            $values->issuedBy,
            $values->dueDate,
            $values->dateOfIssue,
            $values->customer->name,
            $values->customer->address,
            $values->customer->companyNumber,
            $values->customer->vat,
            $values->customer->vatPayer,
            null,
        );
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getIssuedBy(): string
    {
        return $this->issuedBy;
    }

    public function setIssuedBy(string $issuedBy): void
    {
        $this->issuedBy = $issuedBy;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(DateTimeImmutable $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getDateOfIssue(): DateTimeImmutable
    {
        return $this->dateOfIssue;
    }

    public function setDateOfIssue(DateTimeImmutable $dateOfIssue): void
    {
        $this->dateOfIssue = $dateOfIssue;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getVariableSymbol(): VariableSymbol
    {
        return $this->variableSymbol;
    }

    public function setVariableSymbol(VariableSymbol $variableSymbol): void
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getTransaction(): Transaction|null
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction|null $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getClosedAt(): DateTimeImmutable|null
    {
        return $this->closedAt;
    }

    public function setClosedAt(DateTimeImmutable|null $closedAt): void
    {
        $this->closedAt = $closedAt;
    }

    public function getClosedByUsername(): string|null
    {
        return $this->closedByUsername;
    }

    public function setClosedByUsername(string|null $closedByUsername): void
    {
        $this->closedByUsername = $closedByUsername;
    }

    public function getItems(): Collection|null
    {
        return $this->items;
    }

    public function setItems(Collection|null $items): void
    {
        $this->items = $items;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getCompanyNumber(): string|null
    {
        return $this->companyNumber;
    }

    public function setCompanyNumber(string|null $companyNumber): void
    {
        $this->companyNumber = $companyNumber;
    }

    public function getVatNumber(): string|null
    {
        return $this->vatNumber;
    }

    public function setVatNumber(string|null $vatNumber): void
    {
        $this->vatNumber = $vatNumber;
    }

    public function isVatPayer(): bool
    {
        return $this->vatPayer;
    }

    public function setVatPayer(bool $vatPayer): void
    {
        $this->vatPayer = $vatPayer;
    }
}
