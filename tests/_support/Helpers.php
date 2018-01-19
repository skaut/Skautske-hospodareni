<?php

use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;

class Helpers
{

    public static function createAccountNumber(): AccountNumber
    {
        return new AccountNumber(NULL, '2000942144', '2010');
    }

    /**
     * @return EmailTemplate[]
     */
    public static function createEmails(): array
    {
        return [
            EmailType::PAYMENT_INFO => new EmailTemplate('test subject', 'test body'),
        ];
    }

}
