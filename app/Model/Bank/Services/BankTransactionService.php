<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionImportManager;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Transaction;
use App\Model\Payment\Fio\IFioClient;
use App\Model\Payment\Repositories\IBankAccountRepository;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use InvalidArgumentException;

use function explode;
use function sprintf;
use function trim;

class BankTransactionService
{
    public function __construct(
        private readonly IBankAccountRepository $bankAccounts,
        private readonly BankTransactionRepository $bankTransactions,
        private readonly BankTransactionImportManager $importManager,
        private readonly IFioClient $fio,
        private readonly GpcParser $gpcParser,
        private readonly BankTransactionKeyGenerator $transactionKeyGenerator,
    ) {
    }

    /** @return list<Transaction> */
    public function getTransactions(int $bankAccountId, int $daysBack): array
    {
        $account = $this->bankAccounts->find($bankAccountId);
        $today = ChronosDate::today();

        return $this->getTransactionsForPeriod($account, $today->subDays($daysBack), $today);
    }

    /** @return list<Transaction> */
    public function getTransactionsForPeriod(BankAccount $bankAccount, ChronosDate $since, ChronosDate $until): array
    {
        return array_map(
            static fn (BankTransaction $transaction): Transaction => $transaction->toModel(),
            $this->getPersistentTransactionsForPeriod($bankAccount, $since, $until),
        );
    }

    /** @return list<BankTransaction> */
    public function getPersistentTransactionsForPeriod(BankAccount $bankAccount, ChronosDate $since, ChronosDate $until): array
    {
        if ($bankAccount->getTransactionSource()->value === BankTransactionSource::FIO->value) {
            $this->importManager->importFioTransactions(
                $bankAccount,
                $this->fio->getTransactions($since, $until, $bankAccount),
                new DateTimeImmutable(),
            );
        }

        return $this->getStoredTransactionsForPeriod($bankAccount, $since, $until);
    }

    /** @return list<BankTransaction> */
    public function getStoredTransactionsForPeriod(BankAccount $bankAccount, ChronosDate $since, ChronosDate $until): array
    {
        return $this->bankTransactions->findByAccountAndDateRange(
            $bankAccount,
            $since->toNative()->setTime(0, 0, 0),
            $until->toNative()->setTime(23, 59, 59),
        );
    }

    public function importGpcTransactions(int $bankAccountId, string $fileName, string $contents, string $importedBy): BankTransactionImportBatch
    {
        $bankAccount = $this->bankAccounts->find($bankAccountId);

        if ($bankAccount->getTransactionSource()->value !== BankTransactionSource::GPC->value) {
            throw new InvalidArgumentException('GPC import lze použít pouze pro účty se zdrojem GPC.');
        }

        $targetAccountNumber = $bankAccount->getNumber()->getNumberWithPrefixAndBankCode();
        $parsedFile = $this->gpcParser->parseFile(
            $targetAccountNumber,
            $contents,
            $this->transactionKeyGenerator,
        );

        if (
            $parsedFile->statementAccountNumber !== null
            && ! $this->hasSameStatementAccount($parsedFile->statementAccountNumber, $targetAccountNumber)
        ) {
            throw new InvalidArgumentException(sprintf('GPC soubor patri k uctu %s a nelze jej importovat k uctu %s.', $parsedFile->statementAccountNumber, $targetAccountNumber));
        }

        return $this->importManager->importGpcTransactions(
            $bankAccount,
            $fileName,
            $contents,
            $importedBy,
            $parsedFile->transactions,
            new DateTimeImmutable(),
        );
    }

    public function hasTransactions(BankAccount $bankAccount): bool
    {
        return $this->bankTransactions->hasTransactionsForBankAccount($bankAccount);
    }

    private function hasSameStatementAccount(string $left, string $right): bool
    {
        return $this->normalizeStatementAccount($left) === $this->normalizeStatementAccount($right);
    }

    private function normalizeStatementAccount(string $accountNumber): string
    {
        $parts = explode('/', $accountNumber, 2);

        return trim($parts[0]);
    }
}
