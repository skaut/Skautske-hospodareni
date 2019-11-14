<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisEventId;

/**
 * @see EventParticipantStatisticsQueryHandler
 */
final class EventParticipantStatisticsQuery
{
    /** @var SkautisEventId */
    private $id;

    public function __construct(SkautisEventId $id)
    {
        $this->id = $id;
    }

    public function getId() : SkautisEventId
    {
        return $this->id;
    }
}
