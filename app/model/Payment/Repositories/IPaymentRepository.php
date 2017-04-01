<?php

namespace Model\Payment\Repositories;

use Model\Payment\Payment;

interface IPaymentRepository
{

    public function save(Payment $payment): void;

}
