<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\Bank\Fio\Transaction;
use Model\Payment\Fio\IFioClient;

class FioClientStub implements IFioClient
{
    
    /** @var Transaction[] */
    private $transactions = [];

    public function setTransactions(array $transactions)
    {
        $this->transactions = $transactions;
    }


    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account): array
    {
        return $this->transactions;
    }

}
