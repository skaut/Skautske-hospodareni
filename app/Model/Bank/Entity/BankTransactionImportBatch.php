<?php

declare(strict_types=1);

namespace App\Model\Bank\Entity;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Repository\BankTransactionImportBatchRepository;
use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: BankTransactionImportBatchRepository::class)]
#[Table(name: 'bank_transaction_import_batch')]
class BankTransactionImportBatch extends AbstractIdEntity
{
    #[ManyToOne(targetEntity: BankAccount::class)]
    #[JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BankAccount $bankAccount;

    #[Column(type: Types::STRING, length: 20)]
    private string $source;

    #[Column(type: Types::STRING, length: 255)]
    private string $fileName;

    #[Column(type: Types::STRING, length: 64)]
    private string $fileHash;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $importedAt;

    #[Column(type: Types::STRING, length: 255)]
    private string $importedBy;

    #[Column(type: Types::INTEGER)]
    private int $transactionCount;

    #[Column(type: Types::INTEGER)]
    private int $newTransactionCount = 0;

    public function __construct(
        BankAccount $bankAccount,
        BankTransactionSource $source,
        string $fileName,
        string $fileHash,
        DateTimeImmutable $importedAt,
        string $importedBy,
        int $transactionCount,
    ) {
        $this->bankAccount = $bankAccount;
        $this->source = $source->value;
        $this->fileName = $fileName;
        $this->fileHash = $fileHash;
        $this->importedAt = $importedAt;
        $this->importedBy = $importedBy;
        $this->transactionCount = $transactionCount;
    }

    public function markCompleted(int $newTransactionCount): void
    {
        $this->newTransactionCount = $newTransactionCount;
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function getSource(): BankTransactionSource
    {
        return BankTransactionSource::from($this->source);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileHash(): string
    {
        return $this->fileHash;
    }

    public function getImportedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function getImportedBy(): string
    {
        return $this->importedBy;
    }

    public function getTransactionCount(): int
    {
        return $this->transactionCount;
    }

    public function getNewTransactionCount(): int
    {
        return $this->newTransactionCount;
    }
}
