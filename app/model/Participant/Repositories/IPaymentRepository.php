<?php

declare(strict_types=1);

namespace Model\Budget\Repositories;

use Model\Participant\Payment;
use Model\Participant\PaymentNofFound;

interface IPaymentRepository
{
    /**
     * @throws PaymentNofFound
     */
    public function findPayment(int $id) : Payment;


    public function savePayment(Payment $payment) : void;

    public function deletePayment(Payment $payment) : void;
}
