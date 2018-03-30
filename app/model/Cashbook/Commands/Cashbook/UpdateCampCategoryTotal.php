<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UpdateCampCategoryTotalHandler;

/**
 * Updates category total for camp in Skautis
 * @see UpdateCampCategoryTotalHandler
 */
final class UpdateCampCategoryTotal
{

    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $categoryId;

    public function __construct(CashbookId $cashbookId, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

}
