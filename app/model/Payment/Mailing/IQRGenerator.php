<?php

namespace Model\Payment\Mailing;

use Model\Payment\InvalidBankAccountException;

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
