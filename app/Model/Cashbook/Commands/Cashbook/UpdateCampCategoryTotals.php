<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\UpdateCampCategoryTotalHandler;

/**
 * Updates category total for camp in Skautis.
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
