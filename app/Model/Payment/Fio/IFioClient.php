<?php

declare(strict_types=1);

namespace App\Model\Payment\Fio;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Exception\BankTimeLimit;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\Exception\BankWrongTokenAccount;
use App\Model\Bank\Transaction;
use App\Model\Payment\TokenNotSet;
use Cake\Chronos\ChronosDate;

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
