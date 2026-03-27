<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\ReadModel\QueryHandlers\CampFunctionsHandler;
use App\Model\Event\SkautisCampId;

/** @see CampFunctionsHandler */
class CampFunctions
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
