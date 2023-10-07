<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read SkautisEducationCourseId $id
 * @property-read string|null $displayName
 * @property-read int $estimatedPersonDays
 */
class EducationCourse
{
    use SmartObject;

    public function __construct(
        private SkautisEducationCourseId $id,
        private string|null $displayName,
        private int $estimatedPersonDays,
    ) {
    }

    public function getId(): SkautisEducationCourseId
    {
        return $this->id;
    }

    public function getDisplayName(): string|null
    {
        return $this->displayName;
    }

    public function getEstimatedPersonDays(): int
    {
        return $this->estimatedPersonDays;
    }
}
