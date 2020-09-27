<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\CategorySummary;
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

            $this->mockCategorySummary(100, Operation::INCOME, false),
            $this->mockCategorySummary(1000, Operation::EXPENSE, false),
            $this->mockCategorySummary(2000, Operation::INCOME, true),
        ]);
    }

    private function mockCategorySummary(float $amount, string $operation, bool $virtualCategory) : CategorySummary
    {
        return m::mock(CategorySummary::class, [
            'getTotal' => MoneyFactory::fromFloat($amount),
            'isVirtual' => $virtualCategory,
            'isIncome' => $operation === Operation::INCOME,
        ]);
    }

    /**
     * @param CategorySummary[] $summaries
     */
    private function assertBalance(Money $expectedBalance, array $summaries) : void
    {
        $cashbookId = Cashbook\CashbookId::fromString(self::CASHBOOK_ID);

        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->withArgs(static function (CategoriesSummaryQuery $query) {
                return $query->getCashbookId()->toString() === self::CASHBOOK_ID;
            })
            ->andReturn($summaries);

        $handler = new FinalRealBalanceQueryHandler($queryBus);

        $actualBalance = $handler(new FinalRealBalanceQuery($cashbookId));

        $this->assertTrue($expectedBalance->equals($actualBalance));
    }
}
