<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/** @property-read SkautisEducationCourseParticipationStatsId $id */
class EducationCourseParticipationStats
{
    use SmartObject;

    public function __construct(
        private SkautisEducationCourseParticipationStatsId $id,
        private int $accepted,
        private int $capacity,
    ) {
    }

    public function getId(): SkautisEducationCourseParticipationStatsId
    {
        return $this->id;
    }

    public function getAccepted(): int
    {
        return $this->accepted;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }
}
