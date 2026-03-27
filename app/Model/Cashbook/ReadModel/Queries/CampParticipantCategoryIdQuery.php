<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ParticipantType;
use App\Model\Cashbook\ReadModel\QueryHandlers\CampParticipantCategoryIdQueryHandler;
use App\Model\Event\SkautisCampId;

/** @see CampParticipantCategoryIdQueryHandler */
final class CampParticipantCategoryIdQuery
{
    public function __construct(private SkautisCampId $campId, private ParticipantType $participantType)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

    public function getParticipantType(): ParticipantType
    {
        return $this->participantType;
    }
}
