<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\BankAccount\AccountNumber;
use Money\Money;

final class Repayment
{
    public function __construct(private AccountNumber $targetAccount, private Money $amount, private string $messageForRecipient)
    {
    }

    public function getTargetAccount(): AccountNumber
    {
        return $this->targetAccount;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getMessageForRecipient(): string
    {
        return $this->messageForRecipient;
    }
}
