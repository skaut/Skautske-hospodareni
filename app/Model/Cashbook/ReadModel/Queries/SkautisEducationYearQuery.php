<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\QueryHandlers\SkautisEducationYearQueryHandler;

/** @see SkautisEducationYearQueryHandler */
final class SkautisEducationYearQuery
{
    public function __construct(private CashbookId $cashbookId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
