<?php

declare(strict_types=1);

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

/**
 * @todo Use this event for logging
 */
class ChitWasRemoved
{
    private CashbookId $cashbookId;

    private string $chitPurpose;

    public function __construct(CashbookId $cashbookId, string $chitPurpose)
    {
        $this->cashbookId  = $cashbookId;
        $this->chitPurpose = $chitPurpose;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitPurpose() : string
    {
        return $this->chitPurpose;
    }
}
