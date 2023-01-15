<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantBalanceQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use Model\Common\Services\QueryBus;

class EventParticipantBalanceQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EventParticipantBalanceQuery $query): float
    {
        $participantIncome = $this->queryBus->handle(new EventParticipantIncomeQuery($query->getEventId()));
        $chitSum           = $this->queryBus->handle(new ParticipantChitSumQuery($query->getCashbookId()));

        return $participantIncome - $chitSum;
    }
}
