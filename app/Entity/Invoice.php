<?php

declare(strict_types=1);

namespace Entity;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Entity\Embeddable\AccountNumber;
use Entity\Embeddable\InvoiceCustomer;
use Entity\Embeddable\InvoiceSupplier;
use Entity\Embeddable\Transaction;
use Enum\InvoicePaymentType;
use Enum\InvoiceState;
use LogicException;
use Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use Model\Payment\VariableSymbol;
use Nette\Utils\ArrayHash;

#[Entity]
#[Table(name: 'invoice')]
class Invoice extends AbstractIdEntity
{
    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $invoiceId = null;

    #[ManyToOne(targetEntity: InvoiceSequence::class, inversedBy: 'invoices')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private InvoiceSequence $sequence;

    #[Embedded(class: InvoiceSupplier::class)]
    private InvoiceSupplier $supplier;

    #[Embedded(class: InvoiceCustomer::class)]
    private InvoiceCustomer $customer;

    #[Column(type: Types::STRING, length: 255)]
    private string $issuedBy;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $dueDate;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $dateOfIssue;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private DateTimeImmutable $dateOfTaxPayment;

    #[Column(type: 'string_enum', length: 20)]
    private string $state = InvoiceState::ISSUED;

    #[Column(type: 'variable_symbol', length: 10, nullable: false)]
    private VariableSymbol $variableSymbol;

    #[Embedded(class: Transaction::class, columnPrefix: false)]
    #[Nullable]
    private ?Transaction $transaction = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $closedByUsername = null;

    /** @var Collection<int, InvoiceItem>&iterable<InvoiceItem> */
    #[OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[Column(type: 'string_enum', length: 20)]
    private string $paymentType;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $bankName;

    #[Embedded(class: AccountNumber::class)]
    private AccountNumber $accountNumber;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iban = null;
    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $bic = null;

    public function __construct(
        InvoiceSequence $sequence,
        InvoiceSupplier $invoiceSupplier,
        InvoiceCustomer $invoiceCustomer,
        string $issuedBy,
        DateTimeImmutable $dueDate,
        DateTimeImmutable $dateOfIssue,
        DateTimeImmutable $dateOfTaxPayment,
        InvoicePaymentType $paymentType,
        AccountNumber $accountNumber,
        ?string $bankName = null,
        ?string $iban = null,
        ?string $bic = null,
        ?VariableSymbol $variableSymbol = null,
    ) {
        $this->sequence = $sequence;
        $this->supplier = $invoiceSupplier;
        $this->customer = $invoiceCustomer;
        $this->items = new ArrayCollection();
        $this->issuedBy = $issuedBy;
        $this->dueDate = $dueDate;
        $this->dateOfIssue = $dateOfIssue;
        $this->dateOfTaxPayment = $dateOfTaxPayment;
        $this->accountNumber = $accountNumber;
        $this->bankName = $bankName;
        $this->iban = $iban;
        $this->bic = $bic;
        $this->paymentType = $paymentType->value;
        $this->variableSymbol = $variableSymbol ?? new VariableSymbol('111');
    }

    public static function formForm(ArrayHash $values, InvoiceSequence $invoiceSequence, InvoiceSupplier $supplier, InvoiceCustomer $customer): self
    {
        $invoice = new self(
            $invoiceSequence,
            $supplier,
            $customer,
            $values->issuedBy,
            $values->dueDate,
            $values->dateOfIssue,
            $values->dateOfTaxPayment,
            constant(InvoicePaymentType::class.'::'.$values->paymentType),
            $invoiceSequence->getBankAccount()->getNumber(),
            $invoiceSequence->getBankAccount()->getNumber()->getBankName(),
            $invoiceSequence->getBankAccount()->getNumber()->getIban(),
            $invoiceSequence->getBankAccount()->getNumber()->getBic(),
        );

        return $invoice;
    }

    public function getTotalAmount(): BigDecimal
    {
        $sum = BigDecimal::zero();
        foreach ($this->getItems() as $item) {
            $sum = $sum->plus($item->getTotalPrice());
        }

        return $sum;
    }

    public function getInvoiceNumber(): string
    {
        return sprintf('%s-%s/%s', $this->sequence->getSequence(), $this->getInvoiceId(), $this->sequence->getYear());
    }

    /** @return Collection<int, InvoiceItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): void
    {
        if (! $this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }
    }

    public function removeItem(InvoiceItem $item): void
    {
        $this->items->removeElement($item);
    }

    public function getPaymentType(): InvoicePaymentType
    {
        return InvoicePaymentType::from($this->paymentType);
    }

    public function getInvoiceId(): int
    {
        if ($this->invoiceId === null) {
            throw new LogicException('Invoice ID has not been assigned yet.');
        }

        return $this->invoiceId;
    }

    public function setInvoiceId(int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getSequence(): InvoiceSequence
    {
        return $this->sequence;
    }

    public function setSequence(InvoiceSequence $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getSupplier(): InvoiceSupplier
    {
        return $this->supplier;
    }

    public function setSupplier(InvoiceSupplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getCustomer(): InvoiceCustomer
    {
        return $this->customer;
    }

    public function setCustomer(InvoiceCustomer $customer): void
    {
        $this->customer = $customer;
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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeImmutable $closedAt): void
    {
        $this->closedAt = $closedAt;
    }

    public function getClosedByUsername(): ?string
    {
        return $this->closedByUsername;
    }

    public function setClosedByUsername(?string $closedByUsername): void
    {
        $this->closedByUsername = $closedByUsername;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getAccountNumber(): AccountNumber
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(AccountNumber $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): void
    {
        $this->bic = $bic;
    }

    public function getDateOfTaxPayment(): DateTimeImmutable
    {
        return $this->dateOfTaxPayment;
    }

    public function setDateOfTaxPayment(DateTimeImmutable $dateOfTaxPayment): void
    {
        $this->dateOfTaxPayment = $dateOfTaxPayment;
    }
}
