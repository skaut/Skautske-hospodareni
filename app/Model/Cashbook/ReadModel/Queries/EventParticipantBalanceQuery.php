<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Event\SkautisEventId;

/** @see EventParticipantBalanceQueryHandler */
final class EventParticipantBalanceQuery
{
    private SkautisEventId $eventId;

    public function __construct(SkautisEventId $campId, private CashbookId $cashbookId)
    {
        $this->eventId = $campId;
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
