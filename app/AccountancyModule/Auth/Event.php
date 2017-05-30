<?php

namespace App\AccountancyModule\Auth;

use Nette\StaticClass;

final class Event
{
    use StaticClass;

    public const UPDATE_FUNCTION = [self::class, "EV_EventGeneral_UPDATE_Function"];

}
