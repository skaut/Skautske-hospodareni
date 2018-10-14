<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use function array_map;

final class CategoryPairsQueryHandlerTest extends Unit
{
    private const CATEGORIES = [
        [1, 'Název 1', Operation::INCOME],
        [2, 'Název 2', Operation::EXPENSE],
    ];

    private const CASHBOOK_TYPE = CashbookType::EVENT;
    private const CASHBOOK_ID   = '123';

    public function testReturnAllCategoriesIfOperationIsNotPassed() : void
    {
        $handler = $this->createHandler();

        $this->assertSame([
            1 => 'Název 1',
            2 => 'Název 2',
        ], $handler->handle(
            new CategoryPairsQuery(CashbookId::fromString(self::CASHBOOK_ID))
        ));
    }

    public function testReturnOnlyIncomeCategoriesWhenOperationTypeIsPassed() : void
    {
        $handler = $this->createHandler();

        $this->assertSame([1 => 'Název 1'], $handler->handle(
            new CategoryPairsQuery(CashbookId::fromString(self::CASHBOOK_ID), Operation::get(Operation::INCOME))
        ));
    }

    private function createHandler() : CategoryPairsQueryHandler
    {
        $categories = array_map(function (array $category) : ICategory {
            return m::mock(ICategory::class, [
                'getId' => $category[0],
                'getName' => $category[1],
                'getOperationType' => Operation::get($category[2]),
            ]);
        }, self::CATEGORIES);

        $categoryRepository = m::mock(CategoryRepository::class);
        $categoryRepository->shouldReceive('findForCashbook')
            ->once()
            ->withArgs(function (CashbookId $cashbookId, CashbookType $type) : bool {
                return $cashbookId->equals(CashbookId::fromString(self::CASHBOOK_ID))
                    && $type->equalsValue(self::CASHBOOK_TYPE);
            })
            ->andReturn($categories);

        $cashbookRepository = m::mock(ICashbookRepository::class);
        $cashbookRepository->shouldReceive('find')
            ->once()
            ->withArgs(function (CashbookId $id) {
                return $id->equals(CashbookId::fromString(self::CASHBOOK_ID));
            })
            ->andReturn(m::mock(Cashbook::class, [
                'getId' => CashbookId::fromString(self::CASHBOOK_ID),
                'getType' => CashbookType::get(self::CASHBOOK_TYPE),
            ]));

        return new CategoryPairsQueryHandler($categoryRepository, $cashbookRepository);
    }
}
