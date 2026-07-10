<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Services;

use App\Model\Cashbook\CampBudgetUpdateNotAllowed;
use App\Model\Cashbook\Cashbook\CashbookId;
use InvalidArgumentException;

interface ICampCategoryUpdater
{
    /**
     * Updates statistics in Skautis.
     *
     * @param array<int, float> $cashbookTotals Category totals indexed by category ID
     *
     * @throws InvalidArgumentException
     * @throws CampBudgetUpdateNotAllowed
     */
    public function updateCategories(CashbookId $cashbookId, array $cashbookTotals): void;
}
