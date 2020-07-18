<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\AddInverseChitHandler;

/**
 * @see AddInverseChitHandler
 */
final class AddInverseChit
{
    private CashbookId $originalCashbookId;

    private CashbookId $targetCashbookId;

    private int $chitId;

    public function __construct(CashbookId $originalCashbookId, CashbookId $targetCashbookId, int $chitId)
    {
        $this->originalCashbookId = $originalCashbookId;
        $this->targetCashbookId   = $targetCashbookId;
        $this->chitId             = $chitId;
    }

    public function getOriginalCashbookId() : CashbookId
    {
        return $this->originalCashbookId;
    }

    public function getTargetCashbookId() : CashbookId
    {
        return $this->targetCashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }
}
