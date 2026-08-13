<?php

declare(strict_types=1);

namespace App\Model\Bank\Repository;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Infrastructure\Repository\AbstractRepository;

/** @extends AbstractRepository<BankAccount> */
class BankAccountRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return BankAccount::class;
    }

    /**
     * @return BankAccount[]
     */
    public function findByUnitId(int $toInt): array
    {
        return $this->findBy(['unitId' => $toInt]);
    }
}
