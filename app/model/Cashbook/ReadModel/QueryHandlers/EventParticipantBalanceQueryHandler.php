<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\QueryBus\IQueryBus;
use Model\Cashbook\ReadModel\Queries\EventParticipantBalanceQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use Money\Money;

class EventParticipantBalanceQueryHandler
{
    /** @var IQueryBus */
    private $queryBus;

    public function __construct(IQueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function handle(EventParticipantBalanceQuery $query) : Money
    {
        /** @var Money $participantIncome */
        $participantIncome = $this->queryBus->handle(new EventParticipantIncomeQuery($query->getEventId()));
        /** @var Money $chitSum */
        $chitSum = $this->queryBus->handle(new ParticipantChitSumQuery($query->getCashbookId()));

        return $participantIncome->subtract($chitSum);
    }
}
