<?php

namespace Model\Cashbook\Services;

interface ICampCategoryUpdater
{

    /**
     * Updates statistics in Skautis
     * @param int $cashbookId Local camp/cashbook ID
     * @throws \InvalidArgumentException
     */
    public function updateCategory(int $cashbookId, int $categoryId, float $total): void;

}
