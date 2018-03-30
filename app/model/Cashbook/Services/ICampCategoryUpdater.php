<?php

namespace Model\Cashbook\Services;

use Model\Cashbook\Cashbook\CashbookId;

interface ICampCategoryUpdater
{

    /**
     * Updates statistics in Skautis
     * @param float[] $totals Category totals indexed by category ID
     * @throws \InvalidArgumentException
     */
    public function updateCategories(CashbookId $cashbookId, array $totals): void;

}
