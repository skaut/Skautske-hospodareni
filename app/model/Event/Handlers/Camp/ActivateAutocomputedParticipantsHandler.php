<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Camp;

use Model\Event\Commands\Camp\ActivateAutocomputedParticipants;
use Skautis\Skautis;

final class ActivateAutocomputedParticipantsHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(ActivateAutocomputedParticipants $command): void
    {
        $this->skautis->event->eventCampUpdateAdult(
            [
                'ID' => $command->getCampId()->toInt(),
                'IsRealAutoComputed' => 1,
            ],
            'eventCamp',
        );
    }
}
