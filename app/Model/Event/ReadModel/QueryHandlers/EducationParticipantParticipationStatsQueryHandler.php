<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationParticipantParticipationStats;
use App\Model\Event\ReadModel\Queries\EducationParticipantParticipationStatsQuery;
use App\Model\Skautis\Factory\EducationParticipantParticipationStatsFactory;
use LogicException;
use Skautis\Skautis;

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
            'ID_Grant' => $query->getGrantId(),
        ]);

        if (is_object($participantParticipationStats)) {
            return [];
        }

        $result = [];
        foreach ($participantParticipationStats as $stat) {
            $stat = $this->educationParticipantParticipationStatsFactory->create($stat);
            if (! $stat instanceof EducationParticipantParticipationStats) {
                throw new LogicException('Assertion failed.');
            }
            $result[$stat->getId()->toInt()] = $stat;
        }

        return $result;
    }
}
