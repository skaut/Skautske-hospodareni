<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\Enum\CampState;
use App\Model\Event\ReadModel\Queries\CampStatsQuery;
use Skautis\Skautis;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class CampStatsQueryHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return array<string, int> */
    public function __invoke(CampStatsQuery $query): array
    {
        $camps = $this->skautis->event->EventCampAll(['Year' => $query->getYear()]);

        $counters = [];
        foreach (CampState::toArray() as $state) {
            $counters[$state] = 0;
        }

        foreach ($camps as $camp) {
            $state = $camp->ID_EventCampState;
            if (! isset($counters[$state])) {
                continue;
            }

            ++$counters[$state];
        }

        return $counters;
    }
}
