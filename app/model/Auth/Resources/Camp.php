<?php

declare(strict_types=1);

namespace Model\Auth\Resources;

use Nette\StaticClass;

final class Camp
{

    use StaticClass;

    public const ACCESS_DETAIL = [self::class, 'EV_EventCamp_DETAIL'];
    public const ACCESS_FUNCTIONS = [self::class, 'EV_EventFunction_ALL_EventCamp'];
    public const ACCESS_PARTICIPANTS = [self::class, 'EV_ParticipantCamp_ALL_EventCamp'];

    public const UPDATE = [self::class, 'EV_EventCamp_UPDATE'];

    // TODO: Come up with better names
    public const UPDATE_REAL = [self::class, 'EV_EventCamp_UPDATE_Real'];
    public const UPDATE_REAL_COST = [self::class, 'EV_EventCamp_UPDATE_RealTotalCostBeforeEnd'];


    public const ACCESS_PARTICIPANT_DETAIL = [self::class, 'EV_ParticipantCamp_DETAIL'];
    public const ADD_PARTICIPANT = [self::class, 'EV_ParticipantCamp_INSERT_EventCamp'];
    public const REMOVE_PARTICIPANT = [self::class, 'EV_ParticipantCamp_DELETE'];
    public const UPDATE_PARTICIPANT = [self::class, 'EV_ParticipantCamp_UPDATE_EventCamp'];
    public const SET_AUTOMATIC_PARTICIPANTS_CALCULATION = [self::class, 'EV_EventCamp_UPDATE_Adult'];

    public const UPDATE_BUDGET = [self::class, 'EV_EventCampStatement_UPDATE_EventCamp'];
}
