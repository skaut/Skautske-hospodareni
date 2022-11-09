<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\BankAccount\AccountNumber;

class RepaymentCandidateFactory
{
    public static function create(Payment $payment): RepaymentCandidate
    {
        return new RepaymentCandidate(
            $payment->getId(),
            $payment->getPersonId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getTransaction() !== null && $payment->getTransaction()->getBankAccount() !== null ? AccountNumber::fromString($payment->getTransaction()->getBankAccount()) : null,
        );
    }
}
