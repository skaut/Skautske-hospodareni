<?php

declare(strict_types=1);

namespace App\Model\Participant\Repositories;

use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\PaymentNotFound;

interface IPaymentRepository
{
    /** @throws PaymentNotFound */
    public function findByParticipant(int $id, Payment\EventType $eventType): Payment;

    /** @return Payment[] */
    public function findByEvent(Event $event): array;

    public function save(Payment $payment): void;

    public function remove(Payment $payment): void;
}
