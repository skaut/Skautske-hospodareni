<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\Queries;

use Model\Event\SkautisEducationId;
use Model\Event\SkautisGrantId;
use Model\Skautis\ReadModel\QueryHandlers\EducationBudgetQueryHandler;

/** @see EducationBudgetQueryHandler */
final class EducationBudgetQuery
{
    public function __construct(private SkautisEducationId $educationId, private SkautisGrantId $grantId)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }

    public function getGrantId(): SkautisGrantId
    {
        return $this->grantId;
    }
}
