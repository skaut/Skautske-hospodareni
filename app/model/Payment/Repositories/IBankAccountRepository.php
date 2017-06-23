<?php

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFoundException;

interface IBankAccountRepository
{

    /**
     * @param int $id
     * @throws BankAccountNotFoundException
     * @return BankAccount
     */
    public function find(int $id): BankAccount;

    public function save(BankAccount $account): void;

    /**
     * @param int $unitId
     * @return BankAccount[]
     */
    public function findByUnit(int $unitId): array;

}
