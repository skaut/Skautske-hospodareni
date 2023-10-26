<?php

declare(strict_types=1);

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Education
{
    use StaticClass;

    public const TABLE = 'EV_EventEducation';

    public const ACCESS_DETAIL    = [self::class, 'EV_EventEducationOther_DETAIL'];
    public const UPDATE           = [self::class, 'EV_EventEducation_UPDATE'];
    public const ACCESS_FUNCTIONS = [self::class, 'EV_EventFunction_ALL_EventEducationLeader'];

    public const ACCESS_PARTICIPANTS        = [self::class, 'EV_ParticipantEducation_ALL_EventEducation'];
    public const UPDATE_PARTICIPANT         = [self::class, 'EV_ParticipantEducation_UPDATE_EventEducation'];
    public const ACCESS_COURSE_PARTICIPANTS = [self::class, 'EV_EventEducationCourse_ALL_EventEducation_Participants'];

    public const ACCESS_BUDGET = [self::class, 'GR_Statement_ALL_EventEducation'];
}
