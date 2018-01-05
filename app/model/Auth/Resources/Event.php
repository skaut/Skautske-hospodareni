<?php

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Event
{
    use StaticClass;

    public const UPDATE_FUNCTION = [self::class, "EV_EventGeneral_UPDATE_Function"];
    public const UPDATE = [self::class, "EV_EventGeneral_UPDATE"];
    public const DELETE = [self::class, "EV_EventGeneral_DELETE"];
    public const ACCESS_DETAIL = [self::class, "EV_EventGeneral_DETAIL"];
}
