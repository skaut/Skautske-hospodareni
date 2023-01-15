<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;

/** @see CampParticipantStatisticsQueryHandler */
final class CampParticipantStatisticsQuery
{
    public function __construct(private SkautisCampId $id)
    {
    }

    public function getId(): SkautisCampId
    {
        return $this->id;
    }
}
