<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Utils\MoneyFactory;
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
        return m::mock(Chit::class, [
            'getBody'       => new Cashbook\ChitBody(null, new Cake\Chronos\Date('2017-11-17'), null, new Cashbook\Amount($amount), "pro test"),
            'getCategory'   => new Cashbook\Category(1, Operation::get($operation)),
        ]);
    }

    /**
     * @param Chit[] $chits
     */
    private function assertBalance(Money $expectedBalance, array $chits) : void
    {
        $cashbookId = Cashbook\CashbookId::fromString(self::CASHBOOK_ID);

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with($cashbookId)
            ->andReturn(
                m::mock(Cashbook::class, ['getChits' => $chits])
            );

        $handler = new FinalCashBalanceQueryHandler($repository);

        $actualBalance = $handler->handle(new FinalCashBalanceQuery($cashbookId));

        $this->assertTrue($expectedBalance->equals($actualBalance));
    }
}
