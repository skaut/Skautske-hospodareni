<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\UpdateEducationCategoryTotalHandler;

/**
 * Updates category total for education event in Skautis.
 *
 * @see UpdateEducationCategoryTotalHandler
 */
final class UpdateEducationCategoryTotals
{
    public function __construct(private CashbookId $cashbookId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
