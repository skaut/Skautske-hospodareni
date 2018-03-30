<?php

use Model\Common\AbstractAggregate;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;

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

    public static function createEmptyPaymentDefaults(): PaymentDefaults
    {
        return new PaymentDefaults(NULL, NULL, NULL, NULL);
    }

    public static function getValidDueDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2018-01-19'); // https://youtu.be/kfVsfOSbJY0?t=44s
    }

    /**
     * @param object $aggregate
     */
    public static function assignIdentity($aggregate, int $id): void
    {
        $class = new ReflectionClass(get_class($aggregate));

        $idProperty = $class->getProperty('id');
        $idProperty->setAccessible(TRUE);

        $idProperty->setValue($aggregate, $id);
    }

}
