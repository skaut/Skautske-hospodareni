<?php

declare(strict_types=1);

namespace App\Components\Factories\Participants;

use App\Components\Participants\PersonPicker;
use App\Model\Common\UnitId;
use App\Model\DTO\Participant\Participant;

interface IPersonPickerFactory
{
    /** @param Participant[] $currentParticipants */
    public function create(UnitId $userUnitId, array $currentParticipants): PersonPicker;
}
