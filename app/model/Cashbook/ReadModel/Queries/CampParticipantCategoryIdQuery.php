<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ParticipantType;
use Model\Cashbook\ReadModel\QueryHandlers\CampParticipantCategoryIdQueryHandler;
use Model\Event\SkautisCampId;

/**
 * @see CampParticipantCategoryIdQueryHandler
 */
final class CampParticipantCategoryIdQuery
{

    /** @var SkautisCampId */
    private $campId;

    /** @var ParticipantType */
    private $participantType;

    public function __construct(SkautisCampId $campId, ParticipantType $participantType)
    {
        $this->campId = $campId;
        $this->participantType = $participantType;
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
