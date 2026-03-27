<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use Codeception\Test\Unit;
use Mockery as m;

final class CategoryListQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = '9cca6b0d-5555-4de0-bf86-6cdd7dbad545';

    public function testReturnsCategoriesIndexedById(): void
    {
        $categories = [
            m::mock(ICategory::class, [
                'getId' => 1,
                'getName' => 'Income',
                'getShortcut' => 'INC',
                'getOperationType' => Operation::INCOME(),
                'isVirtual' => false,
            ]),
            m::mock(ICategory::class, [
                'getId' => 2,
                'getName' => 'Expense',
                'getShortcut' => 'EXP',
                'getOperationType' => Operation::EXPENSE(),
                'isVirtual' => true,
            ]),
        ];

        $cashbooks = m::mock(ICashbookRepository::class);
        $cashbooks->shouldReceive('find')
            ->once()
            ->andReturn(m::mock(Cashbook::class, [
                'getId' => $this->cashbookId(),
                'getType' => CashbookType::get(CashbookType::EVENT),
            ]));

        $categoryRepository = m::mock(CategoryRepository::class);
        $categoryRepository->shouldReceive('findForCashbook')
            ->once()
            ->withArgs(fn (CashbookId $id, CashbookType $type): bool => $id->equals($this->cashbookId()) && $type->equalsValue(CashbookType::EVENT))
            ->andReturn($categories);

        $result = (new CategoryListQueryHandler($cashbooks, $categoryRepository))(new CategoryListQuery($this->cashbookId()));

        self::assertSame([1, 2], array_keys($result));
        self::assertSame('Income', $result[1]->getName());
        self::assertTrue($result[1]->isIncome());
        self::assertTrue($result[2]->isVirtual());
    }

    private function cashbookId(): CashbookId
    {
        return CashbookId::fromString(self::CASHBOOK_ID);
    }
}
