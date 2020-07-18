<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Event\SkautisEventId;

/**
 * @see EventParticipantBalanceQueryHandler
 */
final class EventParticipantBalanceQuery
{
    private SkautisEventId $eventId;

    private CashbookId $cashbookId;

    public function __construct(SkautisEventId $campId, CashbookId $cashbookId)
    {
        $this->eventId    = $campId;
        $this->cashbookId = $cashbookId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }
}
