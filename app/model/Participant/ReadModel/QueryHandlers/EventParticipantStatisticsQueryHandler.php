<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use Model\DTO\Participant\Statistics;
use function count;

final class EventParticipantStatisticsQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(EventParticipantStatisticsQuery $query) : Statistics
    {
        $participants = $this->queryBus->handle(new EventParticipantListQuery($query->getId()));
        $days         = 0;
        foreach ($participants as $p) {
            $days += $p->getDays();
        }

        return new Statistics((int) $days, count($participants));
    }
}
