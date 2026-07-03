<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Common\Embeddable\AccountNumber;

class RepaymentCandidateFactory
{
    public static function create(Payment $payment): RepaymentCandidate
    {
        return new RepaymentCandidate(
            $payment->getId(),
            $payment->getPersonId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getTransaction()?->getBankAccount() !== null ? AccountNumber::fromString($payment->getTransaction()->getBankAccount()) : null,
        );
    }
}
