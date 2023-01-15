<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

/** @see ParticipantStatisticsQueryHandler */
final class ParticipantStatisticsQuery
{
    public function __construct(private SkautisCampId|SkautisEventId $id)
    {
    }

    public function getId(): SkautisCampId|SkautisEventId
    {
        return $this->id;
    }
}
