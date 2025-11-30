<?php

declare(strict_types=1);

namespace Repository;

use Entity\BankAccount;

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
