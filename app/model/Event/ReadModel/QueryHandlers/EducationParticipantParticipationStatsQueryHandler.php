<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationParticipantParticipationStats;
use Model\Event\ReadModel\Queries\EducationParticipantParticipationStatsQuery;
use Model\Skautis\Factory\EducationParticipantParticipationStatsFactory;
use Skautis\Skautis;

use function assert;
use function is_object;

class EducationParticipantParticipationStatsQueryHandler
{
    public function __construct(private Skautis $skautis, private EducationParticipantParticipationStatsFactory $educationParticipantParticipationStatsFactory)
    {
    }

    /** @return array<int, EducationParticipantParticipationStats> Education participant participation statistics indexed by ID */
    public function __invoke(EducationParticipantParticipationStatsQuery $query): array
    {
        $participantParticipationStats = $this->skautis->Grants->ParticipantCourseTermAll([
            'ID_Grant' => $query->getgrantId(),
        ]);

        if (is_object($participantParticipationStats)) {
            return [];
        }

        $result = [];
        foreach ($participantParticipationStats as $stat) {
            $stat = $this->educationParticipantParticipationStatsFactory->create($stat);
            assert($stat instanceof EducationParticipantParticipationStats);

            $result[$stat->getId()->toInt()] = $stat;
        }

        return $result;
    }
}
