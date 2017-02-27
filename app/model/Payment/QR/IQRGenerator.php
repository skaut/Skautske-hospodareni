<?php

namespace Model\Payment\QR;

use Model\DTO\Payment\Payment;

interface IQRGenerator
{

    public function generate(?string $bankAccount, Payment $payment) : string;

}
