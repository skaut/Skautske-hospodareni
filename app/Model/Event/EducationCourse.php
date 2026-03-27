<?php

declare(strict_types=1);

namespace App\Model\Event;

use Nette\SmartObject;

/**
 * @property SkautisEducationCourseId $id
 * @property string|null              $displayName
 * @property int|null                 $estimatedPersonDays
 */
class EducationCourse
{
    use SmartObject;

    public function __construct(
        private SkautisEducationCourseId $id,
        private ?string $displayName,
        private ?int $estimatedPersonDays,
    ) {
    }

    public function getId(): SkautisEducationCourseId
    {
        return $this->id;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getEstimatedPersonDays(): ?int
    {
        return $this->estimatedPersonDays;
    }
}
