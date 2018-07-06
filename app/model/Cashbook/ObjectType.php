<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Consistence\Enum\Enum;

class ObjectType extends Enum
{
    public const CAMP  = 'camp';
    public const EVENT = 'general';
    public const UNIT  = 'unit';
}
