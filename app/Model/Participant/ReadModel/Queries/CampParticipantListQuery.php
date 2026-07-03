<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\CampParticipantListQueryHandler;
use App\Model\Event\SkautisCampId;

/** @see CampParticipantListQueryHandler */
final class CampParticipantListQuery
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $id)
    {
        $this->campId = $id;
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
