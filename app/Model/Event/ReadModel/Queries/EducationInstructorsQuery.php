<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\ReadModel\QueryHandlers\EducationInstructorsQueryHandler;

/** @see EducationInstructorsQueryHandler */
final class EducationInstructorsQuery
{
    public function __construct(private int $eventEducationId)
    {
    }

    public function getEventEducationId(): int
    {
        return $this->eventEducationId;
    }
}
