<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisEducationId;

/**
 * @see EducationCashbookIdQueryHandler
 */
final class EducationCashbookIdQuery
{
    private SkautisEducationId $educationId;

    public function __construct(SkautisEducationId $eventId)
    {
        $this->educationId = $eventId;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
