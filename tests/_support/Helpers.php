<?php

declare(strict_types=1);

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Cashbook\Chit;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\ChitNumber;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Cashbook\Recipient;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group\PaymentDefaults;
use Cake\Chronos\ChronosDate;
use Mockery as m;

class Helpers
{
    public static function createAccountNumber(): AccountNumber
    {
        return new AccountNumber(null, '2000942144', '2010');
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
        return new PaymentDefaults(null, null, null, null);
    }

    public static function getValidDueDate(): ChronosDate
    {
        return new ChronosDate('2018-01-19'); // https://youtu.be/kfVsfOSbJY0?t=44s
    }

    /**
     * @param object $aggregate
     */
    public static function assignIdentity($aggregate, int $id): void
    {
        $class = new ReflectionClass(get_class($aggregate));

        $idProperty = $class->getProperty('id');
        $idProperty->setAccessible(true);

        $idProperty->setValue($aggregate, $id);
    }

    public static function mockChit(int $id, ChronosDate $date, string $operationValue, int $categoryId): Chit
    {
        $operation = Operation::get($operationValue);

        $body = new ChitBody(new ChitNumber('123'), $date, new Recipient('František Maša'));

        return m::mock(Chit::class, [
            'getId' => $id,
            'getBody' => $body,
            'getCategory' => self::mockChitItemCategory($categoryId, $operation),
            'getCategoryId' => $categoryId,
            'getDate' => $date,
            'isLocked' => true,
            'isIncome' => $operation->equalsValue(Operation::INCOME),
            'getOperation' => $operation,
            'getAmount' => new Amount('1'),
        ]);
    }

    public static function addChitToCashbook(
        Cashbook $cashbook,
        ?string $chitNumber = null,
        ?PaymentMethod $paymentMethod = null,
        ?int $categoryId = null,
        ?string $amount = null,
        ?ChronosDate $date = null,
        ?Operation $operation = null,
    ): ChitBody {
        $paymentMethod = $paymentMethod ?? PaymentMethod::CASH();
        $categoryId = $categoryId ?? 1;
        $amount = new Amount($amount ?? '100');

        $chitBody = new ChitBody(
            $chitNumber === null ? null : new ChitNumber($chitNumber),
            $date ?? new ChronosDate(),
            null
        );
        $category = self::mockChitItemCategory($categoryId, $operation ?? null);

        $cashbook->addChit(
            $chitBody,
            $paymentMethod,
            [new Cashbook\ChitItem($amount, $category, 'čokoláda')],
            Helpers::mockCashbookCategories($categoryId)
        );

        return $chitBody;
    }

    public static function mockChitItemCategory(?int $categoryId = null, ?Operation $operation = null): Cashbook\Category
    {
        return new Cashbook\Category(
            $categoryId ?? 1,
            $operation ?? Operation::INCOME()
        );
    }

    /**
     * @return list<ICategory>
     */
    public static function mockCashbookCategories(?int $categoryId = null, ?Operation $operation = null): array
    {
        return [
            $categoryId => m::mock(App\Model\Cashbook\Category::class, [
                'getId' => $categoryId ?? 1,
                'getOperationType' => $operation ?? Operation::INCOME(),
                'isVirtual' => false,
            ]),
        ];
    }
}
