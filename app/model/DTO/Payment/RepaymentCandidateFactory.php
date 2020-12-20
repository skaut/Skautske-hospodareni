<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

class RepaymentCandidateFactory
{
    public static function create(Payment $payment): RepaymentCandidate
    {
        return new RepaymentCandidate(
            $payment->getPersonId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getTransaction() !== null ? $payment->getTransaction()->getBankAccount() : null
        );
    }
}
