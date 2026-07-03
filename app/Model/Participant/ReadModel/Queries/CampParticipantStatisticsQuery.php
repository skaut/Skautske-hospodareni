<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Event\SkautisCampId;

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
