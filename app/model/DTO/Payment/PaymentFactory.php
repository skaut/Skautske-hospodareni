<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\ChronosDate;
use Model\DTO\Payment\Payment as PaymentDTO;
use Model\Payment\Payment;

class PaymentFactory
{
    public static function create(Payment $payment): PaymentDTO
    {
        return new PaymentDTO(
            $payment->getId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmailRecipients(),
            ChronosDate::instance($payment->getDueDate()),
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
