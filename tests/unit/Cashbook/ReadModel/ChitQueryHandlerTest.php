<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Helpers;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;

final class ChitQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = '123';

    private const EXISTING_CHIT_ID = 10;
    private const CATEGORY_ID      = 156;

    public function testUnexistingChit() : void
    {
        $handler = new ChitQueryHandler($this->mockCashbookRepository(), new QueryBus());

        $this->assertNull($handler(new ChitQuery($this->getCashbookId(), 5)));
    }

    private function mockCashbookRepository() : ICashbookRepository
    {
        $cashbook = m::mock(Cashbook::class);

        $cashbook->shouldReceive('getChits')
            ->andReturn([Helpers::mockChit(self::EXISTING_CHIT_ID, new Date(), Operation::INCOME, self::CATEGORY_ID)]);

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->withArgs(function (CashbookId $cashbookId) {
                return $cashbookId->equals($this->getCashbookId());
            })
            ->andReturn($cashbook);

        return $repository;
    }

    private function getCashbookId() : CashbookId
    {
        return CashbookId::fromString(self::CASHBOOK_ID);
    }
}
