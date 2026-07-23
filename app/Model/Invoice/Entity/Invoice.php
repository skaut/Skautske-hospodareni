<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Infrastructure\DoctrineNullableEmbeddables\Nullable;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Enum\InvoiceState;
use App\Model\Payment\VariableSymbol;
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
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;
use LogicException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

use function array_map;
use function array_unique;
use function implode;
use function trim;

#[Entity]
#[Table(name: 'invoice')]
#[UniqueConstraint(name: 'invoice_sequence_number_unique', columns: ['sequence_id', 'invoice_number'])]
class Invoice extends AbstractIdEntity
{
    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $invoiceId = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ManyToOne(targetEntity: InvoiceSequence::class, inversedBy: 'invoices')]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private InvoiceSequence $sequence;

    #[ManyToOne(targetEntity: BankAccount::class)]
    #[JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BankAccount $bankAccount = null;

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
    private ?VariableSymbol $variableSymbol = null;

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

    /** @var Collection<int, InvoiceEmailRecipient>&iterable<InvoiceEmailRecipient> */
    #[OneToMany(mappedBy: 'invoice', targetEntity: InvoiceEmailRecipient::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $emailRecipients;

    /** @var Collection<int, InvoiceSentEmail>&iterable<InvoiceSentEmail> */
    #[OneToMany(mappedBy: 'invoice', targetEntity: InvoiceSentEmail::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sentEmails;

    #[Column(type: 'string_enum', length: 20)]
    private string $paymentType;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $bankName;

    #[Embedded(class: AccountNumber::class)]
    #[Nullable]
    private ?AccountNumber $accountNumber;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iban = null;
    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $bic = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $sentBy = null;

    #[Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $cashReceiptNumber = null;

    public function __construct(
        InvoiceSequence $sequence,
        InvoiceSupplier $invoiceSupplier,
        InvoiceCustomer $invoiceCustomer,
        string $issuedBy,
        DateTimeImmutable $dueDate,
        DateTimeImmutable $dateOfIssue,
        DateTimeImmutable $dateOfTaxPayment,
        InvoicePaymentType $paymentType,
        ?AccountNumber $accountNumber,
        ?string $invoiceNumber = null,
        ?VariableSymbol $variableSymbol = null,
        ?string $bankName = null,
        ?string $iban = null,
        ?string $bic = null,
    ) {
        $this->sequence = $sequence;
        $this->supplier = $invoiceSupplier;
        $this->customer = $invoiceCustomer;
        $this->items = new ArrayCollection();
        $this->emailRecipients = new ArrayCollection();
        $this->sentEmails = new ArrayCollection();
        $this->issuedBy = $issuedBy;
        $this->dueDate = $dueDate;
        $this->dateOfIssue = $dateOfIssue;
        $this->dateOfTaxPayment = $dateOfTaxPayment;
        $this->bankAccount = $sequence->getBankAccount();
        $this->accountNumber = $accountNumber;
        $this->invoiceNumber = $invoiceNumber;
        $this->bankName = $bankName;
        $this->iban = $iban;
        $this->bic = $bic;
        $this->paymentType = $paymentType->value;
        $this->variableSymbol = $variableSymbol;
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
            $values->dateOfIssue,
            constant(InvoicePaymentType::class.'::'.$values->paymentType),
            $invoiceSequence->getBankAccount()?->getNumber(),
            null,
            null,
            $invoiceSequence->getBankAccount()?->getNumber()->getBankName(),
            $invoiceSequence->getBankAccount()?->getNumber()->getIban(),
            $invoiceSequence->getBankAccount()?->getNumber()->getBic(),
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
        if ($this->invoiceNumber !== null) {
            return $this->invoiceNumber;
        }

        return sprintf('%s-%s/%s', $this->sequence->getSequence(), $this->getInvoiceId(), $this->sequence->getYear());
    }

    public function getSequenceDisplayLabel(): string
    {
        return $this->sequence->getDisplayLabel();
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getStoredInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    /** @return Collection<int, InvoiceItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @return list<EmailAddress> */
    public function getEmailRecipients(): array
    {
        return $this->emailRecipients
            ->map(fn (InvoiceEmailRecipient $recipient) => $recipient->getEmailAddress())
            ->getValues();
    }

    /** @param EmailAddress[] $recipients */
    public function updateEmailRecipients(array $recipients): void
    {
        $this->emailRecipients = new ArrayCollection(
            array_map(
                fn (EmailAddress $emailAddress) => new InvoiceEmailRecipient($this, $emailAddress),
                array_unique($recipients),
            ),
        );
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

    /** @return InvoiceSentEmail[] */
    public function getSentEmails(): array
    {
        return $this->sentEmails->toArray();
    }

    public function recordEmailAttempt(EmailType $type, DateTimeImmutable $time, string $senderName, bool $successful = true, ?string $errorMessage = null): void
    {
        $this->sentEmails[] = new InvoiceSentEmail($this, $type, $time, $senderName, $successful, $errorMessage);

        if ($successful && $type->equalsValue(EmailType::INVOICE_INFO)) {
            $this->setDeliveryData($time, $senderName);
        }
    }

    public function recordSentEmail(EmailType $type, DateTimeImmutable $time, string $senderName): void
    {
        $this->recordEmailAttempt($type, $time, $senderName);
    }

    public function getPaymentType(): InvoicePaymentType
    {
        return InvoicePaymentType::from($this->paymentType);
    }

    public function setPaymentType(InvoicePaymentType $paymentType): void
    {
        $this->paymentType = $paymentType->value;
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

    public function assignNumbering(int $invoiceId, string $invoiceNumber, VariableSymbol $variableSymbol): void
    {
        $this->invoiceId = $invoiceId;
        $this->invoiceNumber = $invoiceNumber;
        $this->variableSymbol = $variableSymbol;
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

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function setSupplier(InvoiceSupplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getCustomer(): InvoiceCustomer
    {
        return $this->customer;
    }

    public function getCustomerDisplayName(): string
    {
        return $this->customer->getDisplayName();
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
        if ($this->state !== InvoiceState::CANCELLED && $this->transaction !== null && ! $this->transaction->isEmpty()) {
            return InvoiceState::PAID;
        }

        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getVariableSymbol(): VariableSymbol
    {
        if ($this->variableSymbol === null) {
            throw new LogicException('Variable symbol has not been assigned yet.');
        }

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

        if ($transaction !== null && ! $transaction->isEmpty() && $this->state !== InvoiceState::CANCELLED) {
            $this->state = InvoiceState::PAID;
        }
    }

    public function pairWithBankTransaction(DateTimeImmutable $time, ?string $userName, Transaction $transaction): bool
    {
        if ($this->getPaymentType()->value !== InvoicePaymentType::TRANSFER->value) {
            throw new InvalidArgumentException('Bankovní párování lze použít jen u faktury hrazené převodem.');
        }

        if ($this->isPaid()) {
            return false;
        }

        $this->transaction = $transaction;
        $this->state = InvoiceState::PAID;
        $this->closedAt = $time;
        $this->closedByUsername = $userName;

        return true;
    }

    public function unpairBankTransaction(): bool
    {
        if ($this->getPaymentType()->value !== InvoicePaymentType::TRANSFER->value) {
            throw new InvalidArgumentException('Zrušení bankovního párování lze použít jen u faktury hrazené převodem.');
        }

        if ($this->transaction === null || $this->transaction->isEmpty()) {
            return false;
        }

        $this->transaction = null;
        $this->closedAt = null;
        $this->closedByUsername = null;

        if ($this->state !== InvoiceState::CANCELLED) {
            $this->state = $this->hasBeenDelivered() ? InvoiceState::DELIVERED : InvoiceState::ISSUED;
        }

        return true;
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

    public function getAccountNumber(): ?AccountNumber
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?AccountNumber $accountNumber): void
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

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markAsDelivered(DateTimeImmutable $time, string $senderName): bool
    {
        if ($this->hasBeenDelivered()) {
            return false;
        }

        $this->setDeliveryData($time, $senderName);

        return true;
    }

    public function hasBeenSent(): bool
    {
        return $this->sentAt !== null;
    }

    public function hasBeenDelivered(): bool
    {
        return $this->hasBeenSent();
    }

    public function markAsPaidInCash(string $receiptNumber, DateTimeImmutable $time, string $userName): bool
    {
        $receiptNumber = trim($receiptNumber);

        if ($receiptNumber === '') {
            throw new InvalidArgumentException('Musíte zadat číslo příjmového dokladu.');
        }

        if ($this->getPaymentType()->value !== InvoicePaymentType::CASH->value) {
            throw new InvalidArgumentException('Hotovostní úhradu lze nastavit jen u faktury s formou úhrady "V hotovosti".');
        }

        if ($this->isPaid()) {
            return false;
        }

        $this->state = InvoiceState::PAID;
        $this->closedAt = $time;
        $this->closedByUsername = $userName;
        $this->cashReceiptNumber = $receiptNumber;

        return true;
    }

    public function isPaid(): bool
    {
        return $this->getState() === InvoiceState::PAID;
    }

    public function canBePaidInCash(): bool
    {
        return $this->getPaymentType()->value === InvoicePaymentType::CASH->value && ! $this->isPaid();
    }

    public function hasEmailRecipients(): bool
    {
        return $this->emailRecipients->count() > 0;
    }

    public function getRecipientsString(): string
    {
        return implode(', ', array_map(
            fn (EmailAddress $emailAddress): string => Strings::truncate($emailAddress->getValue(), 35),
            $this->getEmailRecipients(),
        ));
    }

    public function isOverdue(?DateTimeImmutable $today = null): bool
    {
        $today ??= new DateTimeImmutable('today');

        return ! $this->isPaid() && $this->state !== InvoiceState::CANCELLED && $this->dueDate < $today;
    }

    public function getStateLabel(): string
    {
        if ($this->getState() === InvoiceState::CANCELLED) {
            return 'Stornována';
        }

        if ($this->isPaid()) {
            return 'Zaplaceno';
        }

        if ($this->getState() === InvoiceState::DELIVERED) {
            return 'Doručená';
        }

        return 'Vystavená';
    }

    public function canBeEdited(): bool
    {
        return $this->getState() === InvoiceState::ISSUED;
    }

    public function canSendReminder(?DateTimeImmutable $today = null): bool
    {
        return $this->hasBeenDelivered() && $this->isOverdue($today);
    }

    public function hasFailedEmailAttempt(EmailType $type): bool
    {
        foreach ($this->sentEmails as $email) {
            if ($email->getType()->equals($type) && ! $email->wasSuccessful()) {
                return true;
            }
        }

        return false;
    }

    public function setSentAt(?DateTimeImmutable $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function getSentBy(): ?string
    {
        return $this->sentBy;
    }

    public function setSentBy(?string $sentBy): void
    {
        $this->sentBy = $sentBy;
    }

    public function getCashReceiptNumber(): ?string
    {
        return $this->cashReceiptNumber;
    }

    private function setDeliveryData(DateTimeImmutable $time, string $senderName): void
    {
        $this->sentAt = $time;
        $this->sentBy = $senderName;

        if ($this->state === InvoiceState::ISSUED) {
            $this->state = InvoiceState::DELIVERED;
        }
    }
}
