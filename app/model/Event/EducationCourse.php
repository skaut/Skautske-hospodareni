<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read SkautisEducationCourseId $id
 * @property-read string|null $displayName
 * @property-read int|null $estimatedPersonDays
 */
class EducationCourse
{
    use SmartObject;

    public function __construct(
        private SkautisEducationCourseId $id,
        private string|null $displayName,
        private int|null $estimatedPersonDays,
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

    public function getEstimatedPersonDays(): int|null
    {
        return $this->estimatedPersonDays;
    }
}
