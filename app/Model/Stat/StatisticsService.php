<?php

declare(strict_types=1);

namespace App\Model\Stat;

use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Stat\Counter;
use App\Model\Event\Camp;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use App\Model\Event\ReadModel\Queries\CampStatisticsQuery;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use App\Model\Event\ReadModel\Queries\EventStatisticsQuery;
use App\Model\Skautis\ISkautisEvent;
use App\Model\Stat\ReadModel\Queries\LocalUnitStatisticsQuery;
use App\Model\Unit\Unit;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function in_array;

class StatisticsService
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    /** @return array<int, Counter> */
    public function getEventStatistics(Unit $unitTree, int $year): array
    {
        /** @var array<int, Event> $events */
        $events = $this->queryBus->handle(new EventListQuery($year, null));
        /** @var array<int, Camp> $camps */
        $camps = $this->queryBus->handle(new CampListQuery($year));
        /** @var array<int, mixed> $eventStats */
        $eventStats = $this->queryBus->handle(new EventStatisticsQuery(array_keys($events), $year));
        /** @var array<int, mixed> $campStats */
        $campStats = $this->queryBus->handle(new CampStatisticsQuery(array_keys($camps), $year));

        $eventCount = $this->sumUpByEventId($events, array_keys($eventStats));
        $campCount = $this->sumUpByEventId($camps, array_keys($campStats));
        /** @var array<int, Counter> $localStatistics */
        $localStatistics = $this->queryBus->handle(new LocalUnitStatisticsQuery($unitTree->getIdWithChildren(), $year));

        $keys = array_unique(array_merge(array_keys($eventCount), array_keys($campCount), array_keys($localStatistics)));

        $merged = [];
        foreach ($keys as $k) {
            $counter = $localStatistics[$k] ?? new Counter();
            $counter->takeIn(new Counter(
                $eventCount[$k] ?? 0,
                $campCount[$k] ?? 0,
                0,
            ));
            $merged[$k] = $counter;
        }

        foreach ($events as $eventId => $event) {
            $merged[$event->getUnitId()->toInt()] ??= new Counter();
            $merged[$event->getUnitId()->toInt()]->addEvent($event->getState(), array_key_exists($eventId, $eventStats));
        }

        foreach ($camps as $campId => $camp) {
            $merged[$camp->getUnitId()->toInt()] ??= new Counter();
            $merged[$camp->getUnitId()->toInt()]->addCamp($camp->getState(), array_key_exists($campId, $campStats), $camp->getParticipantStatistics() !== null);
        }

        return array_filter(
            $this->countTree($unitTree, $merged),
            function (Counter $c) {
                return ! $c->isEmpty();
            },
        );
    }

    /**
     * @param ISkautisEvent[] $objs
     * @param int[]           $unitKeys
     *
     * @return int[]|array<int, int>
     */
    private function sumUpByEventId(array $objs, array $unitKeys): array
    {
        $cnt = [];
        foreach ($objs as $key => $e) {
            if (! in_array($key, $unitKeys)) {
                continue;
            }

            $unitId = $e->getUnitId()->toInt();
            if (array_key_exists($unitId, $cnt)) {
                ++$cnt[$unitId];
            } else {
                $cnt[$unitId] = 1;
            }
        }

        return $cnt;
    }

    /**
     * @param Counter[] $cntArr
     *
     * @return Counter[]|array<int, Counter>
     */
    private function countTree(Unit $root, array $cntArr): array
    {
        $children = $root->getChildren();

        $res = [$root->getId() => new Counter()];

        if ($children !== null) {
            foreach ($children as $u) {
                $res += $this->countTree($u, $cntArr);
                if (! array_key_exists($u->getId(), $res)) {
                    continue;
                }

                $res[$root->getId()]->takeIn($res[$u->getId()]);
            }
        }

        if (isset($cntArr[$root->getId()])) {
            $res[$root->getId()]->takeIn($cntArr[$root->getId()]);
        }

        return $res;
    }
}
