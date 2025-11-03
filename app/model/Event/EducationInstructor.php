<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/** @property SkautisEducationInstructorId $id */
class EducationInstructor
{
    use SmartObject;

    public function __construct(
        private SkautisEducationInstructorId $id,
    ) {
    }

    public function getId(): SkautisEducationInstructorId
    {
        return $this->id;
    }
}
