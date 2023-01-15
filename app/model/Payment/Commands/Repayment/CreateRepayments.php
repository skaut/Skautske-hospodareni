<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Repayment;

use Cake\Chronos\Date;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\Repayment;

final class CreateRepayments
{
    /** @param Repayment[] $repayments */
    public function __construct(private AccountNumber $sourceAccount, private Date $date, private array $repayments, private string $token)
    {
    }

    public function getSourceAccount(): AccountNumber
    {
        return $this->sourceAccount;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    /** @return Repayment[] */
    public function getRepayments(): array
    {
        return $this->repayments;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
