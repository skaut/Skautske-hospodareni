<?php

declare(strict_types=1);

namespace App\Model\Bank\Entity;

use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use InvalidArgumentException;

#[Entity(repositoryClass: BankTransactionPairingRepository::class)]
#[Table(name: 'bank_transaction_pairing')]
class BankTransactionPairing extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: BankTransaction::class)]
    #[JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BankTransaction $bankTransaction;

    #[Column(name: 'transaction_key', type: Types::STRING, length: 191)]
    private string $transactionKey;

    #[ManyToOne(targetEntity: Payment::class)]
    #[JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Payment $payment = null;

    #[ManyToOne(targetEntity: Invoice::class)]
    #[JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[Column(name: 'pairing_mode', type: Types::STRING, length: 20)]
    private string $pairingMode;

    #[Column(name: 'paired_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $pairedAt;

    #[Column(name: 'paired_by', type: Types::STRING, length: 255, nullable: true)]
    private ?string $pairedBy;

    #[Column(name: 'cancelled_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    #[Column(name: 'cancelled_by', type: Types::STRING, length: 255, nullable: true)]
    private ?string $cancelledBy = null;

    #[Column(name: 'cancellation_reason', type: Types::STRING, length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[Column(name: 'historical_bank_account_id', type: Types::INTEGER, nullable: true)]
    private ?int $historicalBankAccountId;

    #[Column(name: 'historical_bank_account_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $historicalBankAccountName;

    #[Column(name: 'historical_account_number', type: Types::STRING, length: 64, nullable: true)]
    private ?string $historicalAccountNumber;

    #[Column(name: 'historical_bank_code', type: Types::STRING, length: 16, nullable: true)]
    private ?string $historicalBankCode;

    private function __construct(
        ?BankTransaction $bankTransaction,
        string $transactionKey,
        BankTransactionPairingMode $pairingMode,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy,
        ?int $historicalBankAccountId,
        ?string $historicalBankAccountName,
        ?string $historicalAccountNumber,
        ?string $historicalBankCode,
    ) {
        $this->bankTransaction = $bankTransaction;
        $this->transactionKey = $transactionKey;
        $this->pairingMode = $pairingMode->value;
        $this->pairedAt = $pairedAt;
        $this->pairedBy = $pairedBy;
        $this->historicalBankAccountId = $historicalBankAccountId;
        $this->historicalBankAccountName = $historicalBankAccountName;
        $this->historicalAccountNumber = $historicalAccountNumber;
        $this->historicalBankCode = $historicalBankCode;
    }

    public static function forPayment(
        ?BankTransaction $bankTransaction,
        string $transactionKey,
        Payment $payment,
        BankTransactionPairingMode $pairingMode,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy,
        ?int $historicalBankAccountId,
        ?string $historicalBankAccountName,
        ?string $historicalAccountNumber,
        ?string $historicalBankCode,
    ): self {
        $pairing = new self(
            $bankTransaction,
            $transactionKey,
            $pairingMode,
            $pairedAt,
            $pairedBy,
            $historicalBankAccountId,
            $historicalBankAccountName,
            $historicalAccountNumber,
            $historicalBankCode,
        );
        $pairing->payment = $payment;

        return $pairing;
    }

    public static function forInvoice(
        ?BankTransaction $bankTransaction,
        string $transactionKey,
        Invoice $invoice,
        BankTransactionPairingMode $pairingMode,
        DateTimeImmutable $pairedAt,
        ?string $pairedBy,
        ?int $historicalBankAccountId,
        ?string $historicalBankAccountName,
        ?string $historicalAccountNumber,
        ?string $historicalBankCode,
    ): self {
        $pairing = new self(
            $bankTransaction,
            $transactionKey,
            $pairingMode,
            $pairedAt,
            $pairedBy,
            $historicalBankAccountId,
            $historicalBankAccountName,
            $historicalAccountNumber,
            $historicalBankCode,
        );
        $pairing->invoice = $invoice;

        return $pairing;
    }

    public function cancel(DateTimeImmutable $cancelledAt, ?string $cancelledBy = null, ?string $reason = null): void
    {
        if ($this->cancelledAt !== null) {
            throw new InvalidArgumentException('Párování už bylo zrušeno.');
        }

        $this->cancelledAt = $cancelledAt;
        $this->cancelledBy = $cancelledBy;
        $this->cancellationReason = $reason;
    }

    public function getBankTransaction(): ?BankTransaction
    {
        return $this->bankTransaction;
    }

    public function getTransactionKey(): string
    {
        return $this->transactionKey;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getPairingMode(): BankTransactionPairingMode
    {
        return BankTransactionPairingMode::from($this->pairingMode);
    }

    public function getPairedAt(): DateTimeImmutable
    {
        return $this->pairedAt;
    }

    public function getPairedBy(): ?string
    {
        return $this->pairedBy;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getCancelledBy(): ?string
    {
        return $this->cancelledBy;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function getHistoricalBankAccountId(): ?int
    {
        return $this->historicalBankAccountId;
    }

    public function getHistoricalBankAccountName(): ?string
    {
        return $this->historicalBankAccountName;
    }

    public function getHistoricalAccountNumber(): ?string
    {
        return $this->historicalAccountNumber;
    }

    public function getHistoricalBankCode(): ?string
    {
        return $this->historicalBankCode;
    }

    public function isActive(): bool
    {
        return $this->cancelledAt === null;
    }
}
