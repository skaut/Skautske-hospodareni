<?php

declare(strict_types=1);

namespace App\Model\Bank\Manager;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Transaction;
use App\Model\Infrastructure\Manager\AbstractManager;
use DateTimeImmutable;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function hash;
use function in_array;

final class BankTransactionImportManager extends AbstractManager
{
    public function __construct(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        private readonly BankTransactionRepository $bankTransactions,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return BankTransaction::class;
    }

    /** @param list<Transaction> $transactions */
    public function importFioTransactions(BankAccount $bankAccount, array $transactions, DateTimeImmutable $importedAt): int
    {
        return $this->wrapInTransaction(function () use ($bankAccount, $transactions, $importedAt): int {
            return $this->persistNewTransactions($bankAccount, $transactions, $importedAt);
        });
    }

    /** @param list<Transaction> $transactions */
    public function importGpcTransactions(
        BankAccount $bankAccount,
        string $fileName,
        string $contents,
        string $importedBy,
        array $transactions,
        DateTimeImmutable $importedAt,
    ): BankTransactionImportBatch {
        return $this->wrapInTransaction(function () use ($bankAccount, $fileName, $contents, $importedBy, $transactions, $importedAt): BankTransactionImportBatch {
            $batch = new BankTransactionImportBatch(
                $bankAccount,
                BankTransactionSource::GPC,
                $fileName,
                hash('sha256', $contents),
                $importedAt,
                $importedBy,
                count($transactions),
            );
            $this->em->persist($batch);

            $newTransactionCount = $this->persistNewTransactions($bankAccount, $transactions, $importedAt, $batch);
            $batch->markCompleted($newTransactionCount);

            $this->em->persist($batch);
            $this->em->flush();

            return $batch;
        });
    }

    /** @param list<Transaction> $transactions */
    private function persistNewTransactions(
        BankAccount $bankAccount,
        array $transactions,
        DateTimeImmutable $importedAt,
        ?BankTransactionImportBatch $batch = null,
    ): int {
        // Kromě aktuálního klíče se hledá i klíč z dřívější implementace parseru, aby znovunahrání
        // dříve importovaného souboru nevytvořilo duplicity.
        $existingKeys = $this->bankTransactions->findExistingTransactionKeys(
            array_merge([], ...array_map(
                static fn (Transaction $transaction): array => $transaction->getKnownIds(),
                $transactions,
            )),
        );

        $newTransactions = array_values(
            array_filter(
                $transactions,
                static function (Transaction $transaction) use ($existingKeys): bool {
                    foreach ($transaction->getKnownIds() as $key) {
                        if (in_array($key, $existingKeys, true)) {
                            return false;
                        }
                    }

                    return true;
                },
            ),
        );

        foreach ($newTransactions as $transaction) {
            $this->em->persist(new BankTransaction($bankAccount, $transaction, $importedAt, $batch));
        }

        $this->em->flush();

        return count($newTransactions);
    }
}
