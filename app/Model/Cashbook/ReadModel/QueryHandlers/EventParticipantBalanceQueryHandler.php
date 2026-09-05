<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EventParticipantBalanceQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use App\Model\Common\Services\QueryBus;
use Money\Money;

class EventParticipantBalanceQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EventParticipantBalanceQuery $query): Money
    {
        $participantIncome = $this->queryBus->handle(new EventParticipantIncomeQuery($query->getEventId()));
        $chitSum = $this->queryBus->handle(new ParticipantChitSumQuery($query->getCashbookId()));

        return $participantIncome->subtract($chitSum);
    }
}
