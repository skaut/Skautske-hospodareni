<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\BankAccount\AccountNumber;
use Money\Money;

final class Repayment
{
    private AccountNumber $targetAccount;

    private Money $amount;

    private string $messageForRecipient;

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
