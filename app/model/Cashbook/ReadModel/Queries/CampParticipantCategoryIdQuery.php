<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ParticipantType;
use Model\Cashbook\ReadModel\QueryHandlers\CampParticipantCategoryIdQueryHandler;
use Model\Event\SkautisCampId;

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
