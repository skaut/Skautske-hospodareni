<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisEventId;

final class EventCashbookIdQuery
{

    /** @var SkautisEventId */
    private $eventId;

    public function __construct(SkautisEventId $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }

}
