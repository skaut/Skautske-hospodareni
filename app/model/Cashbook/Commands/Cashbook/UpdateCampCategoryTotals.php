<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UpdateCampCategoryTotalHandler;

/**
 * Updates category total for camp in Skautis
 * @see UpdateCampCategoryTotalHandler
 */
final class UpdateCampCategoryTotals
{

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(CashbookId $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

}
