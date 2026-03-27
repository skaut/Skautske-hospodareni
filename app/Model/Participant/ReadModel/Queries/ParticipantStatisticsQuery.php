<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEventId;

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
