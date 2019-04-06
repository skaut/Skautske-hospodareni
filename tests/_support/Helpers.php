<?php

declare(strict_types=1);

use Cake\Chronos\Date;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\ICategory;
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

    public static function getValidDueDate() : Date
    {
        return new Date('2018-01-19'); // https://youtu.be/kfVsfOSbJY0?t=44s
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

    public static function mockChit(int $id, Date $date, string $operationValue, int $categoryId) : Chit
    {
        $operation = Operation::get($operationValue);

        $body = new ChitBody(new ChitNumber('123'), $date, new Recipient('František Maša'), new Amount('1'), 'test');
        return Mockery::mock(Chit::class, [
            'getId' => $id,
            'getBody' => $body,
            'getCategory' => new Cashbook\Category($categoryId, $operation),
            'getCategoryId' => $categoryId,
            'getDate' => $date,
            'isLocked' => true,
            'isIncome' => $operation->equalsValue(Operation::INCOME),
            'getOperation' => $operation,
        ]);
    }

    public static function addChitToCashbook(Cashbook $cashbook, ?string $chitNumber, PaymentMethod $paymentMethod): ChitBody
    {
        $chitBody = new ChitBody(
            $chitNumber === null ? null : new ChitNumber($chitNumber),
            new Date(),
            null,
            new Amount('100'),
            'čokoláda'
        );
        $category = m::mock(ICategory::class, ['getId' => 1, 'getOperationType' => Operation::get(Operation::INCOME)]);
        $cashbook->addChit ($chitBody, $category, $paymentMethod);
        return $chitBody;
    }
}
