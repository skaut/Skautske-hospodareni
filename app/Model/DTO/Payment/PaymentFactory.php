<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\DTO\Payment\Payment as PaymentDTO;
use App\Model\Payment\Payment;
use Cake\Chronos\ChronosDate;

class PaymentFactory
{
    public static function create(Payment $payment): PaymentDTO
    {
        return new PaymentDTO(
            $payment->getId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmailRecipients(),
            new ChronosDate($payment->getDueDate()),
            $payment->getVariableSymbol(),
            $payment->getConstantSymbol(),
            $payment->getNote(),
            $payment->isClosed(),
            $payment->getState(),
            $payment->getTransaction(),
            $payment->getClosedAt(),
            $payment->getClosedByUsername(),
            $payment->getPersonId(),
            $payment->getGroupId(),
            $payment->getSentEmails(),
        );
    }
}
