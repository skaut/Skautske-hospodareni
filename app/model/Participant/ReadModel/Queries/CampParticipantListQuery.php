<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\CampParticipantListQueryHandler;
use Model\Event\SkautisCampId;

/**
 * @see CampParticipantListQueryHandler
 */
final class CampParticipantListQuery
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $id)
    {
        $this->campId = $id;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }
}
