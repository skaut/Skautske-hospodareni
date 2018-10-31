<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\CampCategory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Money\Money;

final class CategoryRepositoryTest extends Unit
{
    private const CASHBOOK_ID = '123';

    private const CAMP_ID = 456;

    public function testResultsForCampContainBothStaticAndSkautisCategories() : void
    {
        $staticCategories = [
            new Category(11, 'test1', 't', Operation::get(Operation::INCOME), [], false,10),
            new Category(50, 'test1', 't', Operation::get(Operation::EXPENSE), [], false,10),
        ];

        $staticCategoryRepository = \Mockery::mock(IStaticCategoryRepository::class);
        $staticCategoryRepository->shouldReceive('findByObjectType')
            ->once()
            ->withArgs([ObjectType::get(ObjectType::CAMP)])
            ->andReturn($staticCategories);

        $campCategories = [
            new CampCategory(2, Operation::get(Operation::EXPENSE), 'name', Money::CZK(0), null, false),
            new CampCategory(4, Operation::get(Operation::INCOME), 'name2', Money::CZK(0), null, false),
        ];

        $campCategoryRepository = \Mockery::mock(ICampCategoryRepository::class);
        $campCategoryRepository->shouldReceive('findForCamp')
            ->once()
            ->withArgs(function (int $id) : bool {
                return $id === self::CAMP_ID;
            })->andReturn($campCategories);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(function (SkautisIdQuery $query): bool {
                return $query->getCashbookId()->toString() === '123';
            })->andReturn(self::CAMP_ID);

        $repository = new CategoryRepository($campCategoryRepository, $staticCategoryRepository, $queryBus);

        $result = $repository->findForCashbook(CashbookId::fromString(self::CASHBOOK_ID), CashbookType::get(CashbookType::CAMP));

        $this->assertSame(array_merge($staticCategories, $campCategories), $result);
    }
}
