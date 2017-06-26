<?php

namespace Model\Payment\Fio;

use DateTimeInterface;
use Model\Bank\Fio\Transaction;
use Model\BankTimeLimitException;
use Model\BankTimeoutException;
use Model\Payment\BankAccount;
use Model\Payment\TokenNotSetException;


interface IFioClient
{

    /**
     * @return Transaction[]
     * @throws TokenNotSetException
     * @throws BankTimeoutException
     * @throws BankTimeLimitException
     */
    public function getTransactions(DateTimeInterface $since, DateTimeInterface $until, BankAccount $account): array;

}
