<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\AddInverseChitHandler;

/** @see AddInverseChitHandler */
final class AddInverseChit
{
    public function __construct(private CashbookId $originalCashbookId, private CashbookId $targetCashbookId, private int $chitId)
    {
    }

    public function getOriginalCashbookId(): CashbookId
    {
        return $this->originalCashbookId;
    }

    public function getTargetCashbookId(): CashbookId
    {
        return $this->targetCashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }
}
