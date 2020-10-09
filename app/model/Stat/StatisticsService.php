<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\DTO\Stat\Counter;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Event\ReadModel\Queries\CampStatisticsQuery;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Event\ReadModel\Queries\EventStatisticsQuery;
use Model\Skautis\ISkautisEvent;
use Model\Unit\Unit;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function in_array;

class StatisticsService
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @return array<int, Counter>
     */
    public function getEventStatistics(Unit $unitTree, int $year) : array
    {
        $events     = $this->queryBus->handle(new EventListQuery($year, null));
        $camps      = $this->queryBus->handle(new CampListQuery($year));
        $eventStats = $this->queryBus->handle(new EventStatisticsQuery(array_keys($events), $year));
        $campStats  = $this->queryBus->handle(new CampStatisticsQuery(array_keys($camps), $year));

        $eventCount = $this->sumUpByEventId($events, array_keys($eventStats));
        $campCount  = $this->sumUpByEventId($camps, array_keys($campStats));

        $keys   = array_keys($eventCount) +array_keys($campCount);
        $merged = [];
        foreach ($keys as $k) {
            $merged[$k] = new Counter(
                $eventCount[$k] ?? 0,
                $campCount[$k] ?? 0
            );
        }

        return array_filter(
            $this->countTree($unitTree, $merged),
            function (Counter $c) {
                return ! $c->isEmpty();
            }
        );
    }

    /**
     * @param ISkautisEvent[] $objs
     * @param int[]           $unitKeys
     *
     * @return int[]|array<int, int>
     */
    private function sumUpByEventId(array $objs, array $unitKeys) : array
    {
        $cnt = [];
        foreach ($objs as $key => $e) {
            if (! in_array($key, $unitKeys)) {
                continue;
            }

            $unitId = $e->getUnitId()->toInt();
            if (array_key_exists($unitId, $cnt)) {
                $cnt[$unitId] += 1;
            } else {
                $cnt[$unitId] = 1;
            }
        }

        return $cnt;
    }

    /**
     * @param Counter[] $cntArr
     *
     * @return Counter[]|array<int, Counter>|null
     */
    private function countTree(Unit $root, array $cntArr) : ?array
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
