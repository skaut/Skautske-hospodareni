<?php

declare(strict_types=1);

namespace App\Model\Skautis\Factory;

use App\Model\Event\EducationCourseParticipationStats;
use App\Model\Event\SkautisEducationCourseParticipationStatsId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationCourseParticipationStatsFactory
{
    public function create(stdClass $skautisEducationCourseParticipationStats): EducationCourseParticipationStats
    {
        return new EducationCourseParticipationStats(
            new SkautisEducationCourseParticipationStatsId($skautisEducationCourseParticipationStats->ID),
            $skautisEducationCourseParticipationStats->ParticipantAcceptedCount,
            $skautisEducationCourseParticipationStats->CapacityCourse,
        );
    }
}
