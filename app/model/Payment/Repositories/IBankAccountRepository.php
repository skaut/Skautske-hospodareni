<?php
/**
 * Created by PhpStorm.
 * User: fmasa
 * Date: 22.2.17
 * Time: 0:22
 */

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;

interface IBankAccountRepository
{

    /**
     * @param int $unitSkautisId
     * @return BankAccount[]
     */
    public function findByUnit(int $unitSkautisId) : array;

}