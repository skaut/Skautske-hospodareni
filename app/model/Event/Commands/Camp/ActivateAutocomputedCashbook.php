<?php

declare(strict_types=1);

namespace Model\Event\Commands\Camp;

use Model\Event\Handlers\Camp\ActivateAutocomputedCashbookHandler;
use Model\Event\SkautisCampId;

/** @see ActivateAutocomputedCashbookHandler */
final class ActivateAutocomputedCashbook
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
