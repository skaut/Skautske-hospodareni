<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Currency;
use Money\Money;

final class FinalBalanceQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = '111';

    public function testCashbookWithoutChitsReturnsZero() : void
    {
        $this->assertBalance(Money::CZK(0), []);
    }

    public function testCashbookWithPositiveAndNegativeChitsReturnsCorrectBalance() : void
    {
        $this->assertBalance(MoneyFactory::fromFloat(1100), [
            $this->mockChit('100', Operation::INCOME),
            $this->mockChit('1000', Operation::EXPENSE),
            $this->mockChit('2000', Operation::INCOME),
        ]);
    }

    private function mockChit(string $amount, string $operation)
    {
        $op = Operation::get($operation);
        return m::mock(Chit::class, [
            'getBody'       => new Cashbook\ChitBody(null, new Date('2017-11-17'), null, new Cashbook\Amount($amount), 'pro test'),
            'getCategory'   => new Category(1, 'catName', new Money($amount, new Currency('CZK')), 'a', $op, $op->equalsValue(Operation::INCOME), false),
        ]);
    }

    /**
     * @param Chit[] $chits
     */
    private function assertBalance(Money $expectedBalance, array $chits) : void
    {
        $cashbookId = Cashbook\CashbookId::fromString(self::CASHBOOK_ID);

        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->withArgs(function (ChitListQuery $query) {
                return $query->getCashbookId()->toString() === self::CASHBOOK_ID && $query->getPaymentMethod() === Cashbook\PaymentMethod::CASH();
            })
            ->andReturn($chits);

        $handler = new FinalCashBalanceQueryHandler($queryBus);

        $actualBalance = $handler->handle(new FinalCashBalanceQuery($cashbookId));

        $this->assertTrue($expectedBalance->equals($actualBalance));
    }
}
