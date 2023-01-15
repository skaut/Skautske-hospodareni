<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UpdateCampCategoryTotalHandler;

/**
 * Updates category total for camp in Skautis
 *
 * @see UpdateCampCategoryTotalHandler
 */
final class UpdateCampCategoryTotals
{
    public function __construct(private CashbookId $cashbookId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
