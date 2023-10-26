<?php

declare(strict_types=1);

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Grant
{
    use StaticClass;

    public const TABLE = 'GR_Grant';

    public const ACCESS_DETAIL = [self::class, 'GR_Grant_DETAIL'];

    public const ACCESS_PARTICIPANT_PARTICIPATION = [self::class, 'GR_ParticipantCourseTerm_ALL_Grant'];

    public const UPDATE_REAL_BUDGET_SPENDING = [self::class, 'GR_Statement_UPDATE_GrantReal'];
}
