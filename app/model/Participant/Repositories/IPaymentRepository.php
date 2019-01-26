<?php

declare(strict_types=1);

namespace Model\Budget\Repositories;

use Model\Event\SkautisEventId;
use Model\Participant\Payment;
use Model\Participant\PaymentNotFound;

interface IPaymentRepository
{
    /**
     * @throws PaymentNotFound
     */
    public function findPayment(int $id) : Payment;

    /**
     * @return Payment[]
     */
    public function findPaymentsByEvent(SkautisEventId $eventId) : array;

    public function savePayment(Payment $payment) : void;

    public function deletePayment(Payment $payment) : void;
}
