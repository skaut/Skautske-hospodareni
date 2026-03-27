<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Camp;

use App\Model\Event\Handlers\Camp\ActivateAutocomputedParticipantsHandler;
use App\Model\Event\SkautisCampId;

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
