<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\FinalBalanceQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Money;

final class FinalBalanceQueryHandlerTest extends Unit
{

    private const CASHBOOK_ID = 111;

    public function testCashbookWithoutChitsReturnsZero(): void
    {
        $this->assertBalance(Money::CZK(0), []);
    }

    public function testCashbookWithPositiveAndNegativeChitsReturnsCorrectBalance(): void
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
            'getAmount'     => new Cashbook\Amount($amount),
            'getCategory'   => new Cashbook\Category(1, Operation::get($operation))
        ]);
    }

    /**
     * @param Chit[] $chits
     */
    private function assertBalance(Money $expectedBalance, array $chits): void
    {
        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(self::CASHBOOK_ID)
            ->andReturn(
                m::mock(Cashbook::class, ['getChits' => $chits])
            );

        $handler = new FinalBalanceQueryHandler($repository);

        $actualBalance = $handler->handle(new FinalBalanceQuery(self::CASHBOOK_ID));

        $this->assertTrue($expectedBalance->equals($actualBalance));
    }

}
