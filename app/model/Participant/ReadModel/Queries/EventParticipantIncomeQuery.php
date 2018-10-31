<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Event\SkautisEventId;

/**
 * @see EventParticipantIncomeQueryHandler
 */
final class EventParticipantIncomeQuery
{
    /** @var SkautisEventId */
    private $eventId;

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(SkautisEventId $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }
}
