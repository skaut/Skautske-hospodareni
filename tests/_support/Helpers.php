<?php

use Model\Payment\BankAccount\AccountNumber;


class Helpers
{

    public static function createAccountNumber(): AccountNumber
    {
        return new AccountNumber(NULL, '2000942144', '2010');
    }

}
