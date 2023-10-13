<?php

declare(strict_types=1);

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Grant
{
    use StaticClass;

    public const TABLE = 'GR_Grant';

    public const ACCESS_PARTICIPANT_PARTICIPATION = [self::class, 'GR_ParticipantCourseTerm_ALL_Grant'];
}
