<?php

declare(strict_types=1);

namespace App\Model\Bank\Repository;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Infrastructure\Repository\AbstractRepository;

use function array_values;

class BankTransactionImportBatchRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return BankTransactionImportBatch::class;
    }

    /** @return list<BankTransactionImportBatch> */
    public function findByBankAccount(BankAccount $bankAccount, int $limit = 20): array
    {
        return array_values(
            $this->createQueryBuilder('entity')
                ->where('entity.bankAccount = :bankAccount')
                ->setParameter('bankAccount', $bankAccount)
                ->orderBy('entity.importedAt', 'DESC')
                ->addOrderBy('entity.id', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult(),
        );
    }
}
