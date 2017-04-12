<?php

namespace Model\Payment\QR;

use Model\Payment\InvalidBankAccountException;
use Model\Payment\Mailing\Payment;

interface IQRGenerator
{

    /**
     * @param string $bankAccount
     * @param Payment $payment
     * @throws InvalidBankAccountException
     * @return string
     */
    public function generate(string $bankAccount, Payment $payment) : string;

}
