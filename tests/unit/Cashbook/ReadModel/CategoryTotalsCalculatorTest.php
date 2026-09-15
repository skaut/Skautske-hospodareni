<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\EducationCategory;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ParticipantType;
use App\Model\Cashbook\ReadModel\CategoryTotalsCalculator;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery as m;
use Money\Money;

use function array_key_exists;

final class CategoryTotalsCalculatorTest extends Unit
{
    private const CATEGORY_INCOME_CHILD_ID = 8888;
    private const CATEGORY_INCOME_ADULT_ID = 9999;
    private const CATEGORY_EDUCATION_FEES_ID = 7777;

    public function testEventCalculation(): void
    {
        $cashbook = $this->mockEventCashbook();
        $calculator = new CategoryTotalsCalculator();
        $totals = $calculator->calculate($cashbook, []);

        $this->assertTrue(MoneyFactory::fromFloat(400.0)->equals($totals[ICategory::CATEGORY_PARTICIPANT_INCOME_ID]));
        $this->assertFalse(array_key_exists(ICategory::CATEGORY_HPD_ID, $totals));
        $this->assertFalse(array_key_exists(ICategory::CATEGORY_REFUND_ID, $totals));
        $this->assertTrue(MoneyFactory::fromFloat(200.0)->equals($totals[2]));
    }

    public function testCampCalculation(): void
    {
        $cashbook = $this->mockCampCashbook();
        $calculator = new CategoryTotalsCalculator();

        $categories = [
            new CampCategory(self::CATEGORY_INCOME_CHILD_ID, Operation::INCOME(), 'Příjmy od dětí a roverů', MoneyFactory::zero(), ParticipantType::CHILD()),
            new CampCategory(self::CATEGORY_INCOME_ADULT_ID, Operation::INCOME(), 'Příjmy od dospělých', MoneyFactory::zero(), ParticipantType::ADULT()),
        ];
        $totals = $calculator->calculate($cashbook, $categories);

        $this->assertTrue(MoneyFactory::fromFloat(250.0)->equals($totals[self::CATEGORY_INCOME_CHILD_ID]));
        $this->assertTrue(MoneyFactory::fromFloat(100.0)->equals($totals[self::CATEGORY_INCOME_ADULT_ID]));
        $this->assertTrue(MoneyFactory::fromFloat(200.0)->equals($totals[2]));
    }

    public function testEducationCalculation(): void
    {
        $cashbook = $this->mockCashbook(
            [
                2 => MoneyFactory::fromFloat(200.0),
                self::CATEGORY_EDUCATION_FEES_ID => MoneyFactory::fromFloat(500.0),
                ICategory::CATEGORY_REFUND_ID => MoneyFactory::fromFloat(120.0),
            ],
            Cashbook\CashbookType::EDUCATION,
        );
        $categories = [
            new EducationCategory(self::CATEGORY_EDUCATION_FEES_ID, Operation::INCOME(), 'Účastnické poplatky', MoneyFactory::zero()),
        ];

        $totals = (new CategoryTotalsCalculator())->calculate($cashbook, $categories);

        // 500 příjem - 120 vratka
        $this->assertTrue(MoneyFactory::fromFloat(380.0)->equals($totals[self::CATEGORY_EDUCATION_FEES_ID]));
        $this->assertFalse(array_key_exists(ICategory::CATEGORY_REFUND_ID, $totals));
        $this->assertTrue(MoneyFactory::fromFloat(200.0)->equals($totals[2]));
    }

    /**
     * Regrese: prázdná vzdělávačka bez vratky a bez kategorie "Účastnické poplatky" nesmí spadnout na
     * MissingCategory – když není žádná vratka k odečtení, kategorie se vůbec nedohledává (jako u akcí).
     */
    public function testEducationCalculationWithoutRefundDoesNotRequireFeesCategory(): void
    {
        $cashbook = $this->mockCashbook([2 => MoneyFactory::fromFloat(200.0)], Cashbook\CashbookType::EDUCATION);

        $totals = (new CategoryTotalsCalculator())->calculate($cashbook, []);

        $this->assertTrue(MoneyFactory::fromFloat(200.0)->equals($totals[2]));
    }

    /** Stejná regrese pro tábor. */
    public function testCampCalculationWithoutRefundDoesNotRequireIncomeCategories(): void
    {
        $cashbook = $this->mockCashbook([2 => MoneyFactory::fromFloat(200.0)], Cashbook\CashbookType::CAMP);

        $totals = (new CategoryTotalsCalculator())->calculate($cashbook, []);

        $this->assertTrue(MoneyFactory::fromFloat(200.0)->equals($totals[2]));
    }

    /**
     * @param array<int, Money> $categoryTotals
     */
    private function mockCashbook(array $categoryTotals, string $type): Cashbook
    {
        $cashbook = m::mock(Cashbook::class);
        $cashbook->shouldReceive('getCategoryTotals')->andReturn($categoryTotals);
        $cashbook->shouldReceive('getType')->andReturn(Cashbook\CashbookType::get($type));

        return $cashbook;
    }

    private function mockEventCashbook(): Cashbook
    {
        $cashbook = m::mock(Cashbook::class);

        $cashbook->shouldReceive('getCategoryTotals')
            ->andReturn([
                2 => MoneyFactory::fromFloat(200.0),
                ICategory::CATEGORY_HPD_ID => MoneyFactory::fromFloat(500.0),
                ICategory::CATEGORY_REFUND_ID => MoneyFactory::fromFloat(155.0),
                ICategory::CATEGORY_PARTICIPANT_INCOME_ID => MoneyFactory::fromFloat(55.0),
            ]);
        $cashbook->shouldReceive('getType')
            ->andReturn(Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT));

        return $cashbook;
    }

    private function mockCampCashbook(): Cashbook
    {
        $cashbook = m::mock(Cashbook::class);

        $cashbook->shouldReceive('getCategoryTotals')
            ->andReturn([
                2 => MoneyFactory::fromFloat(200.0),
                self::CATEGORY_INCOME_CHILD_ID => MoneyFactory::fromFloat(300.0),
                self::CATEGORY_INCOME_ADULT_ID => MoneyFactory::fromFloat(123.0),
                ICategory::CATEGORY_REFUND_CHILD_ID => MoneyFactory::fromFloat(50.0),
                ICategory::CATEGORY_REFUND_ADULT_ID => MoneyFactory::fromFloat(23.0),
            ]);
        $cashbook->shouldReceive('getType')
            ->andReturn(Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP));

        return $cashbook;
    }
}
