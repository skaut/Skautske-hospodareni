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


    /**
     * @param int[] $ids
     * @return BankAccount[]
     * @throws BankAccountNotFoundException
     */
    public function findByIds(array $ids): array;


    public function save(BankAccount $account): void;


    /**
     * @param int $unitId
     * @return BankAccount[]
     */
    public function findByUnit(int $unitId): array;


    public function remove(BankAccount $account): void;

}
