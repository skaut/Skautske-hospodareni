<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EducationCoursesQueryHandler;

/** @see EducationCoursesQueryHandler */
final class EducationCoursesQuery
{
    public function __construct(private int $eventEducationId)
    {
    }

    public function getEventEducationId(): int|null
    {
        return $this->eventEducationId;
    }
}
