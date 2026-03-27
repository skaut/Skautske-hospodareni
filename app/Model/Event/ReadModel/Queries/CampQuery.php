<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\SkautisCampId;

/** @see CampQueryHandler */
class CampQuery
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
