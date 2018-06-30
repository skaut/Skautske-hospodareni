<?php

namespace Model\Auth\Resources;

final class Unit
{
    use \Nette\StaticClass;

    public const EDIT = [self::class, "OU_Unit_UPDATE"];
    public const READ_ACCOUNT = [self::class, "OU_Unit_UPDATE"];
    public const ACCESS_DETAIL = [self::class, "OU_Unit_DETAIL"];

}
