<?php

declare(strict_types=1);

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Camp
{

    use StaticClass;

    public const ACCESS_DETAIL = [self::class, 'EV_EventCamp_DETAIL'];
    public const UPDATE = [self::class, 'EV_EventCamp_UPDATE'];

    // TODO: Come up with better names
    public const UPDATE_REAL = [self::class, 'EV_EventCamp_UPDATE_Real'];
    public const UPDATE_REAL_COST = [self::class, 'EV_EventCamp_UPDATE_RealTotalCostBeforeEnd'];
}
