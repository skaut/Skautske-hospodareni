<?php

declare(strict_types=1);

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

/** @todo Use this event for logging */
class ChitWasRemoved
{
    public function __construct(private CashbookId $cashbookId, private string $chitPurpose)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitPurpose(): string
    {
        return $this->chitPurpose;
    }
}
