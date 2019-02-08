<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\BankAccount\AccountNumber;
use Money\Money;

final class Repayment
{
    /** @var AccountNumber */
    private $targetAccount;

    /** @var Money */
    private $amount;

    /** @var string */
    private $messageForRecipient;

    public function __construct(AccountNumber $targetAccount, Money $amount, string $messageForRecipient)
    {
        $this->targetAccount       = $targetAccount;
        $this->amount              = $amount;
        $this->messageForRecipient = $messageForRecipient;
    }

    public function getTargetAccount() : AccountNumber
    {
        return $this->targetAccount;
    }

    public function getAmount() : Money
    {
        return $this->amount;
    }

    public function getMessageForRecipient() : string
    {
        return $this->messageForRecipient;
    }
}
