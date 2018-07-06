<?php

declare(strict_types=1);

namespace Model\Event\Commands\Camp;

use Model\Event\Handlers\Camp\ActivateAutocomputedParticipantsHandler;
use Model\Event\SkautisCampId;

/**
 * @see ActivateAutocomputedParticipantsHandler
 */
final class ActivateAutocomputedParticipants
{
    /** @var SkautisCampId */
    private $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }
}
