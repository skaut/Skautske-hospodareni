<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\QueryBus\IQueryBus;
use Model\Event\ReadModel\Queries\CampList;
use Model\Event\ReadModel\Queries\EventList;
use Model\Event\ReadModel\Queries\EventsStats;
use Model\Unit\Unit;
use const ARRAY_FILTER_USE_KEY;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function in_array;

class StatService
{
    /** @var IQueryBus */
    private $queryBus;

    public function __construct(IQueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @return mixed[]
     */
    public function getEventStats(Unit $unitTree, ?int $year) : array
    {
        $events = $this->queryBus->handle(new EventList($year, null));
        $camps  = $this->queryBus->handle(new CampList($year));
        $stats  = $this->queryBus->handle(new EventsStats(array_keys($events), $year));

        $allowed = array_keys($stats);

        $eventCount = $this->sumUpByEventId($events, $allowed);
        $campCount  = $this->sumUpByEventId($camps, $allowed);

        $keys   = array_keys($eventCount) +array_keys($campCount);
        $merged = [];
        foreach ($keys as $k) {
            $merged[$k] = [];
            if (array_key_exists($k, $eventCount)) {
                $merged[$k]['events'] = $eventCount[$k];
            }
            if (! array_key_exists($k, $campCount)) {
                continue;
            }

            $merged[$k]['camps'] = $campCount[$k];
        }

        $eventCount = array_filter($this->countTree($unitTree, $merged), function ($o) {
            return $o['events'] !== 0 || $o['camps'] !== 0;
        });

        return $eventCount;
    }

    /**
     * @param mixed[] $objs
     * @param int[]   $unitKeys
     * @return mixed[]
     */
    private function sumUpByEventId(array $objs, array $unitKeys) : array
    {
        //events with chits
        $objs = array_filter(
            $objs,
            function ($key) use ($unitKeys) {
                return in_array($key, $unitKeys);
            },
            ARRAY_FILTER_USE_KEY
        );
        $cnt  = [];
        foreach ($objs as $e) {
            if (array_key_exists($e->getUnitId(), $cnt)) {
                $cnt[$e->getUnitId()] += 1;
            } else {
                $cnt[$e->getUnitId()] = 1;
            }
        }
        return $cnt;
    }

    /**
     * @param mixed[] $cntArr
     * @return mixed[]|null
     */
    private function countTree(Unit $root, array $cntArr) : ?array
    {
        $children = $root->getChildren();

        $res = [
            $root->getId() => ['events' => 0, 'camps' => 0],
        ];

        if ($children !== null) {
            foreach ($children as $u) {
                $res = $res + $this->countTree($u, $cntArr);
                if ($res[$u->getId()]['events'] !== 0) {
                    $res[$root->getId()]['events'] += $res[$u->getId()]['events'];
                }
                if ($res[$u->getId()]['camps'] === 0) {
                    continue;
                }

                $res[$root->getId()]['camps'] += $res[$u->getId()]['camps'];
            }
        }

        if (array_key_exists($root->getId(), $cntArr)) {
            if (array_key_exists('events', $cntArr[$root->getId()])) {
                $res[$root->getId()]['events'] += $cntArr[$root->getId()]['events'];
            }
            if (array_key_exists('camps', $cntArr[$root->getId()])) {
                $res[$root->getId()]['camps'] += $cntArr[$root->getId()]['camps'];
            }
        }
        return $res;
    }
}
