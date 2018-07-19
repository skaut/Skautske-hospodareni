<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFound;

interface IBankAccountRepository
{
    /**
     * @throws BankAccountNotFound
     */
    public function find(int $id) : BankAccount;


    /**
     * @param int[] $ids
     * @return BankAccount[]
     * @throws BankAccountNotFound
     */
    public function findByIds(array $ids) : array;


    public function save(BankAccount $account) : void;


    /**
     * @return BankAccount[]
     */
    public function findByUnit(int $unitId) : array;


    public function remove(BankAccount $account) : void;
}
