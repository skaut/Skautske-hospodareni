<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Camp;

use App\Model\Event\Handlers\Camp\ActivateAutocomputedCashbookHandler;
use App\Model\Event\SkautisCampId;

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
