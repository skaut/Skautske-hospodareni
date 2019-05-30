<?php

declare(strict_types=1);

namespace Model\Participant\Repositories;

use Model\Participant\Payment;
use Model\Participant\Payment\Event;
use Model\Participant\PaymentNotFound;

interface IPaymentRepository
{
    /**
     * @throws PaymentNotFound
     */
    public function find(int $id) : Payment;

    /**
     * @return Payment[]
     */
    public function findByEvent(Event $event) : array;

    public function save(Payment $payment) : void;

    public function remove(Payment $payment) : void;
}
