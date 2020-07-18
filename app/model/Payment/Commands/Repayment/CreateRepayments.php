<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Repayment;

use Cake\Chronos\Date;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\Repayment;

final class CreateRepayments
{
    private AccountNumber $sourceAccount;

    private Date $date;

    /** @var Repayment[] */
    private array $repayments;

    private string $token;

    /**
     * @param Repayment[] $repayments
     */
    public function __construct(AccountNumber $sourceAccount, Date $date, array $repayments, string $token)
    {
        $this->sourceAccount = $sourceAccount;
        $this->date          = $date;
        $this->repayments    = $repayments;
        $this->token         = $token;
    }

    public function getSourceAccount() : AccountNumber
    {
        return $this->sourceAccount;
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    /**
     * @return Repayment[]
     */
    public function getRepayments() : array
    {
        return $this->repayments;
    }

    public function getToken() : string
    {
        return $this->token;
    }
}
