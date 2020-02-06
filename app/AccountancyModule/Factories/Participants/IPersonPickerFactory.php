<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Participants;

use App\AccountancyModule\Components\Participants\PersonPicker;
use Model\Common\UnitId;
use Model\DTO\Participant\Participant;

interface IPersonPickerFactory
{
    /**
     * @param Participant[] $currentParticipants
     */
    public function create(UnitId $userUnitId, array $currentParticipants) : PersonPicker;
}
