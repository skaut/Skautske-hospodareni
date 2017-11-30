<?php

namespace Model\Cashbook\Services;

interface ICampCategoryUpdater
{

    /**
     * Updates statistics in Skautis
     * @param int $cashbookId Local camp/cashbook ID
     * @param float[] $totals Category totals indexed by category ID
     * @throws \InvalidArgumentException
     */
    public function updateCategories(int $cashbookId, array $totals): void;

}
