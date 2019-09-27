<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\ParticipantStatisticsQuery;
use Model\DTO\Participant\Statistics;
use Model\Event\SkautisEventId;
use function count;

final class ParticipantStatisticsQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(ParticipantStatisticsQuery $query) : Statistics
    {
        $participants = $this->queryBus->handle(
            $query->getId() instanceof SkautisEventId
                ? new EventParticipantListQuery($query->getId())
                : new CampParticipantListQuery($query->getId())
        );
        $days         = 0;
        foreach ($participants as $p) {
            $days += $p->getDays();
        }

        return new Statistics((int) $days, count($participants));
    }
}
