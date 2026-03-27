<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;

use function assert;

class EventParticipantIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
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
