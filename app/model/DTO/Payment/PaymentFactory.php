<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use Model\DTO\Payment\Payment as PaymentDTO;
use Model\Payment\Payment;

class PaymentFactory
{
    public static function create(Payment $payment) : PaymentDTO
    {
        return new PaymentDTO(
            $payment->getId(),
            $payment->getName(),
            $payment->getAmount(),
            $payment->getEmail(),
            Date::instance($payment->getDueDate()),
            $payment->getVariableSymbol(),
            $payment->getConstantSymbol(),
            $payment->getNote(),
            $payment->isClosed(),
            $payment->getState(),
            $payment->getTransaction(),
            $payment->getClosedAt(),
            $payment->getPersonId(),
            $payment->getGroupId(),
            $payment->getSentEmails(),
        );
    }
}
