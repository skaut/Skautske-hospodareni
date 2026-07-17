<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationCourseParticipationStats;
use App\Model\Event\ReadModel\Queries\EducationCourseParticipationStatsQuery;
use App\Model\Skautis\Factory\EducationCourseParticipationStatsFactory;
use LogicException;
use Skautis\Skautis;

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
            if (! $stat instanceof EducationCourseParticipationStats) {
                throw new LogicException('Assertion failed.');
            }
            $result[$stat->getId()->toInt()] = $stat;
        }

        return $result;
    }
}
