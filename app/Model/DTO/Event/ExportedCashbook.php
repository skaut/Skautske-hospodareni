<?php

declare(strict_types=1);

namespace App\Model\DTO\Event;

use App\Model\Cashbook\Cashbook\CashbookId;

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
