<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantStatisticsQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Statistics;

use function count;

final class CampParticipantStatisticsQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(CampParticipantStatisticsQuery $query): Statistics
    {
        $participants = $this->queryBus->handle(new CampParticipantListQuery($query->getId()));
        $days = 0;
        foreach ($participants as $p) {
            $days += $p->getDays();
        }

        return new Statistics((int) $days, count($participants));
    }
}
