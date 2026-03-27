<?php

declare(strict_types=1);

namespace App\Model\Bank\Repository;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Infrastructure\Repository\AbstractRepository;
use DateTimeImmutable;

use function array_values;

class BankTransactionRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return BankTransaction::class;
    }

    /** @return list<BankTransaction> */
    public function findByAccountAndDateRange(BankAccount $bankAccount, DateTimeImmutable $since, DateTimeImmutable $until): array
    {
        return array_values(
            $this->createQueryBuilder('entity')
                ->where('entity.bankAccount = :bankAccount')
                ->andWhere('entity.date BETWEEN :since AND :until')
                ->orderBy('entity.date', 'DESC')
                ->addOrderBy('entity.id', 'DESC')
                ->setParameter('bankAccount', $bankAccount)
                ->setParameter('since', $since)
                ->setParameter('until', $until)
                ->getQuery()
                ->getResult(),
        );
    }

    public function findByTransactionKey(string $transactionKey): ?BankTransaction
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.transactionKey = :transactionKey')
            ->setParameter('transactionKey', $transactionKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @param list<string> $transactionKeys
     * @return list<string>
     */
    public function findExistingTransactionKeys(array $transactionKeys): array
    {
        if ($transactionKeys === []) {
            return [];
        }

        return array_values(
            $this->createQueryBuilder('entity')
                ->select('entity.transactionKey')
                ->where('entity.transactionKey IN (:transactionKeys)')
                ->setParameter('transactionKeys', $transactionKeys)
                ->getQuery()
                ->getSingleColumnResult(),
        );
    }

    public function hasTransactionsForBankAccount(BankAccount $bankAccount): bool
    {
        return (int) $this->createQueryBuilder('entity')
            ->select('COUNT(entity.id)')
            ->where('entity.bankAccount = :bankAccount')
            ->setParameter('bankAccount', $bankAccount)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
