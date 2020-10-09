<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisCampId;

/**
 * @see CampQueryHandler
 */
class CampQuery
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }
}
