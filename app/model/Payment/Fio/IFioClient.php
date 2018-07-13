<?php

declare(strict_types=1);

namespace Model\Payment\Fio;

use DateTimeImmutable;
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
    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account) : array;
}
