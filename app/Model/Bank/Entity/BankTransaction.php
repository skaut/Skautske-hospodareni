<?php

declare(strict_types=1);

namespace App\Model\Bank\Entity;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Transaction as BankTransactionModel;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\Infrastructure\Types\Int64Type;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity(repositoryClass: BankTransactionRepository::class)]
#[Table(name: 'bank_transaction')]
#[UniqueConstraint(name: 'bank_transaction_key_unique', columns: ['transaction_key'])]
class BankTransaction extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: BankAccount::class)]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BankAccount $bankAccount;

    #[Column(type: Types::STRING, length: 20)]
    private string $source;

    #[Column(name: 'transaction_key', type: Types::STRING, length: 191)]
    private string $transactionKey;

    #[Column(name: 'source_transaction_id', type: Types::STRING, length: 191, nullable: true)]
    private ?string $sourceTransactionId;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $date;

    #[Column(type: Types::FLOAT)]
    private float $amount;

    #[Column(name: 'counter_account', type: Types::STRING, length: 64, nullable: true)]
    private ?string $counterAccount;

    #[Column(name: 'counter_name', type: Types::STRING, length: 255)]
    private string $counterName;

    #[Column(type: Int64Type::NAME, nullable: true)]
    private ?int $variableSymbol;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $constantSymbol;

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $note;

    #[ManyToOne(targetEntity: BankTransactionImportBatch::class)]
    #[JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BankTransactionImportBatch $importBatch = null;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $importedAt;

    public function __construct(
        BankAccount $bankAccount,
        BankTransactionModel $transaction,
        DateTimeImmutable $importedAt,
        ?BankTransactionImportBatch $importBatch = null,
    ) {
        $this->bankAccount = $bankAccount;
        $this->source = $transaction->getSource()->value;
        $this->transactionKey = $transaction->getId();
        $this->sourceTransactionId = $transaction->getSourceTransactionId();
        $this->date = $transaction->getDate();
        $this->amount = $transaction->getAmount();
        $this->counterAccount = $transaction->getBankAccount();
        $this->counterName = $transaction->getName();
        $this->variableSymbol = $transaction->getVariableSymbol();
        $this->constantSymbol = $transaction->getConstantSymbol();
        $this->note = $transaction->getNote();
        $this->importBatch = $importBatch;
        $this->importedAt = $importedAt;
    }

    public function toModel(): BankTransactionModel
    {
        return new BankTransactionModel(
            $this->transactionKey,
            $this->getSource(),
            $this->date,
            $this->amount,
            $this->counterAccount,
            $this->counterName,
            $this->variableSymbol,
            $this->constantSymbol,
            $this->note,
            $this->sourceTransactionId,
        );
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function getSource(): BankTransactionSource
    {
        return BankTransactionSource::from($this->source);
    }

    public function getTransactionKey(): string
    {
        return $this->transactionKey;
    }

    public function getSourceTransactionId(): ?string
    {
        return $this->sourceTransactionId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCounterAccount(): ?string
    {
        return $this->counterAccount;
    }

    public function getCounterName(): string
    {
        return $this->counterName;
    }

    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getImportBatch(): ?BankTransactionImportBatch
    {
        return $this->importBatch;
    }

    public function getImportedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }
}
