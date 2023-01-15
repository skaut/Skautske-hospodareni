<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisEventId;

/** @see EventParticipantStatisticsQueryHandler */
final class EventParticipantStatisticsQuery
{
    public function __construct(private SkautisEventId $id)
    {
    }

    public function getId(): SkautisEventId
    {
        return $this->id;
    }
}
