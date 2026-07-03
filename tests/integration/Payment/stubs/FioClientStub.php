<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Transaction;
use App\Model\Payment\Fio\IFioClient;
use Cake\Chronos\ChronosDate;

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
