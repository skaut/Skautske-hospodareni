<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\CampCashbookIdQueryHandler;
use App\Model\Event\SkautisCampId;

/** @see CampCashbookIdQueryHandler */
final class CampCashbookIdQuery
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
