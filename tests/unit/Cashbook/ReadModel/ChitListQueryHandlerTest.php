<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Category;
use function array_map;
use function count;

class ChitListQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = 123;

    public function testReturnsChitsWithSorting() : void
    {
        $chits = [
            \Helpers::mockChit(1, new Date('2018-01-02'), Operation::INCOME, 11),
            \Helpers::mockChit(2, new Date('2018-01-01'), Operation::INCOME, 22),
            \Helpers::mockChit(3, new Date('2018-01-01'), Operation::EXPENSE, 44),
            \Helpers::mockChit(4, new Date('2018-01-01'), Operation::INCOME, 33),
        ];

        $cashbookId = $this->getCashbookId();

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with($cashbookId)
            ->andReturn(
                m::mock(Cashbook::class, ['getChits' => $chits])
            );

        $handler = new ChitListQueryHandler($repository, $this->prepareQueryBus());

        $chitDtos = $handler->handle(new ChitListQuery($cashbookId));

        $expectedOrder = [2, 4, 3, 1];
        $this->assertCount(count($expectedOrder), $chitDtos);

        foreach ($expectedOrder as $index => $chitId) {
            $dto  = $chitDtos[$index];
            $chit = $chits[$chitId - 1];

            $this->assertSame($chit->getId(), $dto->getId());
            $this->assertSame($chit->getDate(), $dto->getDate());
            $this->assertSame($chit->getCategoryId(), $dto->getCategory()->getId());
            $this->assertSame($chit->getNumber(), $dto->getNumber());
            $this->assertSame($chit->getAmount(), $dto->getAmount());
            $this->assertSame($chit->getPurpose(), $dto->getPurpose());
            $this->assertSame($chit->getRecipient(), $dto->getRecipient());
            $this->assertSame($chit->isLocked(), $dto->isLocked());
        }
    }

    private function prepareQueryBus() : QueryBus
    {
        $bus = m::mock(QueryBus::class);

        $categories = array_map(function (int $id) : Category {
            return m::mock(Category::class, ['getId' => $id]);
        }, [11, 22, 33, 44]);

        $bus->shouldReceive('handle')
            ->withArgs(function (CategoryListQuery $query) {
                return $query->getCashbookId()->equals($this->getCashbookId());
            })->andReturn($categories);

        return $bus;
    }

    private function getCashbookId() : Cashbook\CashbookId
    {
        return Cashbook\CashbookId::fromInt(self::CASHBOOK_ID);
    }
}
