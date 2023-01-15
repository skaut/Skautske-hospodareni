<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\Queries;

use Model\Event\SkautisCampId;
use Model\Skautis\ReadModel\QueryHandlers\CampBudgetQueryHandler;

/** @see CampBudgetQueryHandler */
final class CampBudgetQuery
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
