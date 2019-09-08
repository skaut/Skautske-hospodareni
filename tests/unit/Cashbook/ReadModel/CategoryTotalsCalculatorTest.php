<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\CampCategory;
use Model\Cashbook\Cashbook;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\CategoryTotalsCalculator;
use Model\Utils\MoneyFactory;
use function array_key_exists;

final class CategoryTotalsCalculatorTest extends Unit
{
    public function testEventCalculation() : void
    {
        $cashbook   = $this->mockCashbook(Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT));
        $categories = [
            new CampCategory(2, Operation::INCOME(), 'prispevek', MoneyFactory::zero(), null),
            new CampCategory(3, Operation::EXPENSE(), 'neco', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_HPD_ID, Operation::INCOME(), 'hpd', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_REFUND_ID, Operation::INCOME(), 'vratky', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, Operation::INCOME(), 'prijmy od ucastniku', MoneyFactory::zero(), null),
        ];
        $calculator = new CategoryTotalsCalculator();
        $totals     = $calculator->calculate($cashbook);

        $this->assertSame(400.0, $totals[ICategory::CATEGORY_PARTICIPANT_INCOME_ID]);
        $this->assertFalse(array_key_exists(ICategory::CATEGORY_HPD_ID, $totals));
        $this->assertFalse(array_key_exists(ICategory::CATEGORY_REFUND_ID, $totals));
        $this->assertSame(200.0, $totals[2]);
    }

    public function testCampCalculation() : void
    {
        $cashbook   = $this->mockCashbook(Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP));
        $categories = [
            new CampCategory(2, Operation::INCOME(), 'prispevek', MoneyFactory::zero(), null),
            new CampCategory(3, Operation::EXPENSE(), 'neco', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_HPD_ID, Operation::INCOME(), 'hpd', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_REFUND_ID, Operation::INCOME(), 'vratky', MoneyFactory::zero(), null),
            new CampCategory(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, Operation::INCOME(), 'prijmy od ucastniku', MoneyFactory::zero(), null),
        ];
        $calculator = new CategoryTotalsCalculator();
        $totals     = $calculator->calculate($cashbook);

        $this->assertSame(55.0, $totals[ICategory::CATEGORY_PARTICIPANT_INCOME_ID]);
        $this->assertSame(500.0, $totals[ICategory::CATEGORY_HPD_ID]);
        $this->assertSame(155.0, $totals[ICategory::CATEGORY_REFUND_ID]);
        $this->assertSame(200.0, $totals[2]);
    }

    private function mockCashbook(Cashbook\CashbookType $type) : Cashbook
    {
        $cashbook = m::mock(Cashbook::class);

        $cashbook->shouldReceive('getCategoryTotals')
            ->andReturn([
                2 => 200.0,
                ICategory::CATEGORY_HPD_ID => 500.0,
                ICategory::CATEGORY_REFUND_ID => 155.0,
                ICategory::CATEGORY_PARTICIPANT_INCOME_ID => 55.0,
            ]);
        $cashbook->shouldReceive('getType')
            ->andReturn($type);

        return $cashbook;
    }
}
