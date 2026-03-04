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
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;

final class CategoriesSummaryQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = 'f63ebca2-9339-44fa-a7c8-c98b81e5a6d2';

    public function testFiltersRefundCategoriesAndMapsTotals(): void
    {
        $cashbook = m::mock(Cashbook::class);
        $cashbook->shouldReceive('getId')->andReturn($this->cashbookId());
        $cashbook->shouldReceive('getType')->andReturn(CashbookType::get(CashbookType::EVENT));
        $cashbook->shouldReceive('getCategoryTotals')
            ->andReturn([
                1 => 125.5,
                ICategory::CATEGORY_REFUND_CHILD_ID => 10.0,
                ICategory::CATEGORY_REFUND_ADULT_ID => 15.0,
            ]);

        $cashbooks = m::mock(ICashbookRepository::class);
        $cashbooks->shouldReceive('find')
            ->once()
            ->andReturn($cashbook);

        $categories = [
            m::mock(ICategory::class, [
                'getId' => 1,
                'getName' => 'Income',
                'getOperationType' => Operation::INCOME(),
                'isVirtual' => false,
            ]),
            m::mock(ICategory::class, [
                'getId' => ICategory::CATEGORY_REFUND_CHILD_ID,
                'getName' => 'Refund child',
                'getOperationType' => Operation::EXPENSE(),
                'isVirtual' => false,
            ]),
            m::mock(ICategory::class, [
                'getId' => ICategory::CATEGORY_REFUND_ADULT_ID,
                'getName' => 'Refund adult',
                'getOperationType' => Operation::EXPENSE(),
                'isVirtual' => false,
            ]),
        ];

        $categoryRepository = m::mock(CategoryRepository::class);
        $categoryRepository->shouldReceive('findForCashbook')
            ->once()
            ->andReturn($categories);

        $result = (new CategoriesSummaryQueryHandler($cashbooks, $categoryRepository))(new CategoriesSummaryQuery($this->cashbookId()));

        self::assertSame([1], array_keys($result));
        self::assertSame('Income', $result[1]->getName());
        self::assertSame('12550', $result[1]->getTotal()->getAmount());
        self::assertTrue($result[1]->isIncome());
    }

    private function cashbookId(): CashbookId
    {
        return CashbookId::fromString(self::CASHBOOK_ID);
    }
}
