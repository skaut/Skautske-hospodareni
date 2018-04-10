<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\Queries;

use Model\Event\SkautisCampId;
use Model\Skautis\ReadModel\QueryHandlers\CampBudgetQueryHandler;

/**
 * @see CampBudgetQueryHandler
 */
final class CampBudgetQuery
{

    /** @var SkautisCampId */
    private $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

}
