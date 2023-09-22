<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UpdateEducationCategoryTotalHandler;

/**
 * Updates category total for education event in Skautis
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
