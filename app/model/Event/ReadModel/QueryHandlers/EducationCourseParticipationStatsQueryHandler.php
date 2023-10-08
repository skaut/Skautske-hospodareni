<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationCourseParticipationStats;
use Model\Event\ReadModel\Queries\EducationCourseParticipationStatsQuery;
use Model\Skautis\Factory\EducationCourseParticipationStatsFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationCourseParticipationStatsQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationCourseParticipationStatsFactory $educationCourseParticipationStatsFactory)
    {
    }

    /** @return array<int, EducationCourseParticipationStats> Education course participation statistics indexed by ID */
    public function __invoke(EducationCourseParticipationStatsQuery $query): array
    {
        $courseParticipationStats = $this->skautis->event->EventEducationCourseAllParticipants([
            'ID_EventEducation' => $query->getEventEducationId(),
        ]);

        if (is_object($courseParticipationStats)) {
            return [];
        }

        $result = [];
        foreach ($courseParticipationStats as $stat) {
            $stat = $this->educationCourseParticipationStatsFactory->create($stat);
            assert($stat instanceof EducationCourseParticipationStats);

            $result[$stat->getId()->toInt()] = $stat;
        }

        return $result;
    }
}
