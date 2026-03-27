<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\Queries;

use App\Model\Event\SkautisEducationId;
use App\Model\Grant\SkautisGrantId;
use App\Model\Skautis\ReadModel\QueryHandlers\EducationBudgetQueryHandler;

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
