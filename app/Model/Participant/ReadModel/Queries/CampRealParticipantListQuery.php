<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\CampRealParticipantListQueryHandler;
use App\Model\Event\SkautisCampId;

/** @see CampRealParticipantListQueryHandler */
final class CampRealParticipantListQuery
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
