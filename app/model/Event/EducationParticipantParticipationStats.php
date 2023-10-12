<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read SkautisEducationParticipantId $id
 * @property-read int $totalDays
 */
class EducationParticipantParticipationStats
{
    use SmartObject;

    public function __construct(
        private SkautisEducationParticipantId $id,
        private int $totalDays,
    ) {
    }

    public function getId(): SkautisEducationParticipantId
    {
        return $this->id;
    }

    public function getTotalDays(): int
    {
        return $this->totalDays;
    }
}
