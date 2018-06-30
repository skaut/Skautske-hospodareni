<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Enum\Enum;

final class ParticipantType extends Enum
{

    public const ADULT = 'adult';
    public const CHILD = 'child';

}
