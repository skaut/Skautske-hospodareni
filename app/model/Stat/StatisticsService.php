<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\QueryBus\IQueryBus;
use Model\DTO\Stat\Counter;
use Model\Event\ReadModel\Queries\CampListQuery;
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
    /** @var IQueryBus */
    private $queryBus;

    public function __construct(IQueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @return Counter[]|array<int, Counter>
     */
    public function getEventStatistics(Unit $unitTree, int $year) : array
    {
        $events = $this->queryBus->handle(new EventListQuery($year, null));
        $camps  = $this->queryBus->handle(new CampListQuery($year));
        $stats  = $this->queryBus->handle(new EventStatisticsQuery(array_keys($events), $year));

        $allowed = array_keys($stats);

        $eventCount = $this->sumUpByEventId($events, $allowed);
        $campCount  = $this->sumUpByEventId($camps, $allowed);

        $keys   = array_keys($eventCount) +array_keys($campCount);
        $merged = [];
        foreach ($keys as $k) {
            $merged[$k] = new Counter(
                $eventCount[$k] ?? 0,
                $campCount[$k] ?? 0
            );
        }

        $eventCount = array_filter(
            $this->countTree($unitTree, $merged),
            function (Counter $c) {
                return ! $c->isEmpty();
            }
        );

        return $eventCount;
    }

    /**
     * @param ISkautisEvent[] $objs
     * @param int[]           $unitKeys
     * @return int[]|array<int, int>
     */
    private function sumUpByEventId(array $objs, array $unitKeys) : array
    {
        $cnt = [];
        foreach ($objs as $key => $e) {
            if (! in_array($key, $unitKeys)) {
                continue;
            }

            if (array_key_exists($e->getUnitId(), $cnt)) {
                $cnt[$e->getUnitId()] += 1;
            } else {
                $cnt[$e->getUnitId()] = 1;
            }
        }
        return $cnt;
    }

    /**
     * @param Counter[] $cntArr
     * @return Counter[]|array<int, Counter>|null
     */
    private function countTree(Unit $root, array $cntArr) : ?array
    {
        $children = $root->getChildren();

        /** @var Counter[] $res */
        $res = [
            $root->getId() => new Counter(),
        ];

        if ($children !== null) {
            foreach ($children as $u) {
                $res = $res + $this->countTree($u, $cntArr);
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
