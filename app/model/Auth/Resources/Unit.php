<?php

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Unit
{
    use StaticClass;

    public const TABLE = "OU_Unit";

    public const EDIT = [self::class, "OU_Unit_UPDATE"];
    public const READ_ACCOUNT = [self::class, "OU_Unit_UPDATE"];
    public const ACCESS_DETAIL = [self::class, "OU_Unit_DETAIL"];

}
