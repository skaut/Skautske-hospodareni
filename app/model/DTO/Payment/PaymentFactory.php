<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use Model\DTO\Payment\Payment as PaymentDTO;
use Model\Payment\Payment;
use function array_map;

class PaymentFactory
{
    public static function create(Payment $payment) : PaymentDTO
    {
        return new PaymentDTO(
            $payment->getId(),
            $payment->getName(),
            $payment->getAmount(),
            array_map(fn(Payment\EmailRecipient $recipient) => $recipient->getEmailAddress(), $payment->getEmailRecipients()),
            Date::instance($payment->getDueDate()),
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
