<?php

namespace App;


interface IAuthorizator
{

    public const EVENT_RESOURCE = "EV_EventGeneral";
    public const UNIT_RESOURCE = "OU_Unit";

    public const AVAILABLE_RESOURCES = [
        self::EVENT_RESOURCE,
        self::UNIT_RESOURCE,
    ];

    public function isAllowed(string $resource, int $id, string $action): bool;

}
