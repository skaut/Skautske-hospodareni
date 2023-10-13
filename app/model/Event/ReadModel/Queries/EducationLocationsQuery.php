<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EducationLocationsQueryHandler;

/** @see EducationLocationsQueryHandler */
final class EducationLocationsQuery
{
    public function __construct(private int $eventEducationId)
    {
    }

    public function getEventEducationId(): int
    {
        return $this->eventEducationId;
    }
}
