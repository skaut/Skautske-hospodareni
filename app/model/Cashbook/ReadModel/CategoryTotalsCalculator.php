<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ICategory;
use function array_key_exists;

final class CategoryTotalsCalculator
{
    /**
     * @return array<int, float>
     */
    public function calculate(Cashbook $cashbook) : array
    {
        $totalByCategories = $cashbook->getCategoryTotals();

        if (! $cashbook->getType()->equalsValue(CashbookType::CAMP)) {
            if (array_key_exists(ICategory::CATEGORY_HPD_ID, $totalByCategories)) {
                $totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] = ($totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] ?? 0) + $totalByCategories[ICategory::CATEGORY_HPD_ID];
                unset($totalByCategories[ICategory::CATEGORY_HPD_ID]);
            }
            if (array_key_exists(ICategory::CATEGORY_REFUND_ID, $totalByCategories)) {
                $totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] = ($totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] ?? 0) - $totalByCategories[ICategory::CATEGORY_REFUND_ID];
                unset($totalByCategories[ICategory::CATEGORY_REFUND_ID]);
            }
        }

        return $totalByCategories;
    }
}
