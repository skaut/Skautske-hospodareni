<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Participant\Participant;

use function assert;

class EventParticipantIncomeQueryHandler
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(EventParticipantIncomeQuery $query): float
    {
        $participants = $this->queryBus->handle(new EventParticipantListQuery($query->getEventId()));

        $participantIncome = 0.0;
        foreach ($participants as $p) {
            assert($p instanceof Participant);
            $participantIncome += $p->getPayment();
        }

        return $participantIncome;
    }
}
