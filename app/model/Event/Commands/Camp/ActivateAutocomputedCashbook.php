<?php

namespace Model\Event\Commands\Camp;

class ActivateAutocomputedCashbook
{

    /** @var int */
    private $campId;

    public function __construct(int $campId)
    {
        $this->campId = $campId;
    }

    public function getCampId(): int
    {
        return $this->campId;
    }

}
