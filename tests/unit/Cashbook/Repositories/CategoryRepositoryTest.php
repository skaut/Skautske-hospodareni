<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Category;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use App\Model\Common\Services\QueryBus;
use Codeception\Test\Unit;
use Mockery;
use Money\Money;

use function array_merge;

final class CategoryRepositoryTest extends Unit
{
    private const CASHBOOK_ID = '9135cc1c-6299-4435-818a-0b5caca1a07f';

    private const CAMP_ID = 456;

    public function testResultsForCampContainBothStaticAndSkautisCategories(): void
    {
        $staticCategories = [
            new Category(11, 'test1', 't', Operation::get(Operation::INCOME), [], false, 10),
            new Category(50, 'test1', 't', Operation::get(Operation::EXPENSE), [], false, 10),
        ];

        $staticCategoryRepository = Mockery::mock(IStaticCategoryRepository::class);
        $staticCategoryRepository->shouldReceive('findByObjectType')
            ->once()
            ->withArgs([ObjectType::get(ObjectType::CAMP)])
            ->andReturn($staticCategories);

        $campCategories = [
            new CampCategory(2, Operation::get(Operation::EXPENSE), 'name', Money::CZK(0), null),
            new CampCategory(4, Operation::get(Operation::INCOME), 'name2', Money::CZK(0), null),
        ];

        $educationCategoryRepository = Mockery::mock(IEducationCategoryRepository::class);

        $campCategoryRepository = Mockery::mock(ICampCategoryRepository::class);
        $campCategoryRepository->shouldReceive('findForCamp')
            ->once()
            ->withArgs(static function (int $id): bool {
                return $id === self::CAMP_ID;
            })->andReturn($campCategories);

        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (SkautisIdQuery $query): bool {
                return $query->getCashbookId()->toString() === self::CASHBOOK_ID;
            })->andReturn(self::CAMP_ID);

        $repository = new CategoryRepository($campCategoryRepository, $educationCategoryRepository, $staticCategoryRepository, $queryBus);

        $result = $repository->findForCashbook(CashbookId::fromString(self::CASHBOOK_ID), CashbookType::get(CashbookType::CAMP));

        $this->assertSame(array_merge($staticCategories, $campCategories), $result);
    }
}
