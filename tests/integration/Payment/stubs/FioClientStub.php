<?php

namespace Model\Payment;

use DateTimeInterface;
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


    public function getTransactions(DateTimeInterface $since, DateTimeInterface $until, BankAccount $account): array
    {
        return $this->transactions;
    }

}
