<?php

declare(strict_types=1);

namespace Model\Event\Commands\Camp;

use Model\Event\SkautisCampId;

class ActivateAutocomputedCashbook
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
