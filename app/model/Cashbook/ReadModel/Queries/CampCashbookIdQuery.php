<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\CampCashbookIdQueryHandler;
use Model\Event\SkautisCampId;

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
