<?php

declare(strict_types=1);

namespace Model\Event\Commands\Camp;

use Model\Event\Handlers\Camp\ActivateAutocomputedParticipantsHandler;
use Model\Event\SkautisCampId;

/** @see ActivateAutocomputedParticipantsHandler */
final class ActivateAutocomputedParticipants
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
