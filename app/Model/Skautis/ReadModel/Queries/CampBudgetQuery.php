<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\Queries;

use App\Model\Event\SkautisCampId;
use App\Model\Skautis\ReadModel\QueryHandlers\CampBudgetQueryHandler;

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
