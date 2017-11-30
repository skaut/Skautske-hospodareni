<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\UpdateCampCategoryTotalHandler;

/**
 * Updates category total for camp in Skautis
 * @see UpdateCampCategoryTotalHandler
 */
final class UpdateCampCategoryTotal
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $categoryId;

    public function __construct(int $cashbookId, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

}
