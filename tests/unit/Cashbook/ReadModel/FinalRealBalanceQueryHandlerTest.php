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
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;
use Model\Utils\MoneyFactory;
use Money\Money;

final class FinalRealBalanceQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = '9a833b92-e507-409a-9459-885ebf505b87';

    public function testCashbookWithoutChitsReturnsZero() : void
    {
        $this->assertBalance(Money::CZK(0), []);
    }

    public function testCashbookWithPositiveAndNegativeChitsReturnsCorrectBalance() : void
    {
        $this->assertBalance(MoneyFactory::fromFloat(-900), [
            $this->mockChit('100', Operation::INCOME, false),
            $this->mockChit('1000', Operation::EXPENSE, false),
            $this->mockChit('2000', Operation::INCOME, true),
        ]);
    }

    private function mockChit(string $amount, string $operation, bool $virtualCategory) : Chit
    {
        $op = Operation::get($operation);

        return m::mock(Chit::class, [
            'getBody'       => new Cashbook\ChitBody(null, new Date('2017-11-17'), null),
            'getSignedAmount' => $amount * ($op->equalsValue(Operation::INCOME) ? 1 : -1),
            'isVirtual' => $virtualCategory,
            'getItems' => [
                new ChitItem(new Cashbook\Amount($amount), new Category(1, 'catName', 'a', $op, $virtualCategory), 'pro test'),
            ],
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
            ->withArgs(static function (ChitListQuery $query) {
                return $query->getCashbookId()->toString() === self::CASHBOOK_ID;
            })
            ->andReturn($chits);

        $handler = new FinalRealBalanceQueryHandler($queryBus);

        $actualBalance = $handler(new FinalRealBalanceQuery($cashbookId));

        $this->assertTrue($expectedBalance->equals($actualBalance));
    }
}
