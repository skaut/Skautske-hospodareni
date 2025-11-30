<?php

declare(strict_types=1);

namespace Model\Payment\Fio;

use Cake\Chronos\ChronosDate;
use Entity\BankAccount;
use Model\Bank\Fio\Transaction;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\BankWrongTokenAccount;
use Model\Payment\TokenNotSet;

interface IFioClient
{
    /**
     * @return Transaction[]
     *
     * @throws TokenNotSet
     * @throws BankTimeout
     * @throws BankTimeLimit
     * @throws BankWrongTokenAccount
     */
    public function getTransactions(ChronosDate $since, ChronosDate $until, BankAccount $account): array;
}
