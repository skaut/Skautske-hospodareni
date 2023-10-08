<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Event\EducationParticipantParticipationStats;
use Model\Event\SkautisEducationParticipantId;
use stdClass;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class EducationParticipantParticipationStatsFactory
{
    public function create(stdClass $skautisEducationParticipantParticipationStats): EducationParticipantParticipationStats
    {
        return new EducationParticipantParticipationStats(
            new SkautisEducationParticipantId($skautisEducationParticipantParticipationStats->ID_ParticipantEducation),
            $skautisEducationParticipantParticipationStats->TotalDays,
        );
    }
}
