<?php

declare(strict_types=1);

namespace Model\Payment;

use DateTimeImmutable;
use Model\Bank\Fio\Transaction;
use Model\Payment\Fio\IFioClient;

class FioClientStub implements IFioClient
{
    /** @var Transaction[] */
    private $transactions = [];

    /**
     * @param Transaction[] $transactions
     */
    public function setTransactions(array $transactions) : void
    {
        $this->transactions = $transactions;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account) : array
    {
        return $this->transactions;
    }
}
