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

    public const ACCESS_PARTICIPANTS = [self::class, 'EV_ParticipantGeneral_ALL_EventGeneral'];
    public const REMOVE_PARTICIPANT = [self::class, 'EV_ParticipantGeneral_DELETE_EventGeneral'];
    public const UPDATE_PARTICIPANT = [self::class, 'EV_ParticipantGeneral_UPDATE_EventGeneral'];

    public const CREATE = [self::class, 'EV_EventGeneral_INSERT'];
    public const OPEN = [self::class, 'EV_EventGeneral_UPDATE_Open'];
    public const CLOSE = [self::class, 'EV_EventGeneral_UPDATE_Close'];

}
