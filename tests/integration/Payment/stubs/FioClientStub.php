<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\ChronosDate;
use Model\Bank\Fio\Transaction;
use Model\Payment\Fio\IFioClient;

class FioClientStub implements IFioClient
{
    /** @var Transaction[] */
    private array $transactions = [];

    /** @param Transaction[] $transactions */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    /** @return Transaction[] */
    public function getTransactions(ChronosDate $since, ChronosDate $until, BankAccount $account): array
    {
        return $this->transactions;
    }
}
