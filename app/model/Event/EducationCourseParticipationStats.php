<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read SkautisEducationCourseParticipationStatsId $id
 * @property-read int $accepted
 * @property-read int|null $capacity
 */
class EducationCourseParticipationStats
{
    use SmartObject;

    public function __construct(
        private SkautisEducationCourseParticipationStatsId $id,
        private int $accepted,
        private int|null $capacity,
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

    public function getCapacity(): int|null
    {
        return $this->capacity;
    }
}
