<?php

declare(strict_types=1);

namespace Model\Payment\Fio;

use Cake\Chronos\Date;
use Model\Bank\Fio\Transaction;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSet;

interface IFioClient
{
    /**
     * @return Transaction[]
     *
     * @throws TokenNotSet
     * @throws BankTimeout
     * @throws BankTimeLimit
     */
    public function getTransactions(Date $since, Date $until, BankAccount $account): array;
}
