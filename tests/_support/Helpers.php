<?php

declare(strict_types=1);

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Operation;
use Model\Payment\BankAccount\AccountNumber;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group\PaymentDefaults;

class Helpers
{
    public static function createAccountNumber() : AccountNumber
    {
        return new AccountNumber(null, '2000942144', '2010');
    }

    /**
     * @return EmailTemplate[]
     */
    public static function createEmails() : array
    {
        return [
            EmailType::PAYMENT_INFO => new EmailTemplate('test subject', 'test body'),
        ];
    }

    public static function createEmptyPaymentDefaults() : PaymentDefaults
    {
        return new PaymentDefaults(null, null, null, null);
    }

    public static function getValidDueDate() : DateTimeImmutable
    {
        return new DateTimeImmutable('2018-01-19'); // https://youtu.be/kfVsfOSbJY0?t=44s
    }

    /**
     * @param object $aggregate
     */
    public static function assignIdentity($aggregate, int $id) : void
    {
        $class = new ReflectionClass(get_class($aggregate));

        $idProperty = $class->getProperty('id');
        $idProperty->setAccessible(true);

        $idProperty->setValue($aggregate, $id);
    }

    public static function mockChit(int $id, Date $date, string $operation, int $categoryId) : Chit
    {
        return Mockery::mock(Chit::class, [
            'getId' => $id,
            'getDate' => $date,
            'getCategory' => new Cashbook\Category($categoryId, Operation::get($operation)),
            'getCategoryId' => $categoryId,
            'getNumber' => new Cashbook\ChitNumber('132'),
            'getAmount' => new Cashbook\Amount('1'),
            'getPurpose' => random_bytes(100),
            'getRecipient' => new Cashbook\Recipient('František Maša'),
            'isLocked' => true,
        ]);
    }
}
