<?php

declare(strict_types=1);

namespace Model\DTO\Event;

use Model\Cashbook\Cashbook\CashbookId;

final class ExportedCashbook
{
    public function __construct(private CashbookId $cashbookId, private string $displayName)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
