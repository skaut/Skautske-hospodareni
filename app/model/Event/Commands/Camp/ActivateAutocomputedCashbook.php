<?php

declare(strict_types=1);

namespace Model\Event\Commands\Camp;

use Model\Event\Handlers\Camp\ActivateAutocomputedCashbookHandler;
use Model\Event\SkautisCampId;

/**
 * @see ActivateAutocomputedCashbookHandler
 */
final class ActivateAutocomputedCashbook
{
    private SkautisCampId $campId;

    public function __construct(SkautisCampId $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }
}
