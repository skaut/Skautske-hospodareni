<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Statistics;

use function count;

final class EventParticipantStatisticsQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EventParticipantStatisticsQuery $query): Statistics
    {
        $participants = $this->queryBus->handle(new EventParticipantListQuery($query->getId()));
        $days = 0;
        foreach ($participants as $p) {
            $days += $p->getDays();
        }

        return new Statistics((int) $days, count($participants));
    }
}
