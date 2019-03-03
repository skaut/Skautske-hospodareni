<?php

declare(strict_types=1);

namespace Model\Participant\Repositories;

use Model\Event\SkautisEventId;
use Model\Participant\Payment;
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
    public function findByEvent(SkautisEventId $eventId) : array;

    public function save(Payment $payment) : void;

    public function remove(Payment $payment) : void;
}
