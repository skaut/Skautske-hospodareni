<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\ReadModel\QueryHandlers\EventFunctionsQueryHandler;
use App\Model\Event\SkautisEventId;

/** @see EventFunctionsQueryHandler */
final class EventFunctions
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
