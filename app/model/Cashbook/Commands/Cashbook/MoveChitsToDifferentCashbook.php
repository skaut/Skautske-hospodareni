<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\MoveChitsToDifferentCashbookHandler;

/** @see MoveChitsToDifferentCashbookHandler */
final class MoveChitsToDifferentCashbook
{
    /** @param int[] $chitIds */
    public function __construct(private array $chitIds, private CashbookId $sourceCashbookId, private CashbookId $targetCashbookId)
    {
    }

    /** @return int[] */
    public function getChitIds(): array
    {
        return $this->chitIds;
    }

    public function getSourceCashbookId(): CashbookId
    {
        return $this->sourceCashbookId;
    }

    public function getTargetCashbookId(): CashbookId
    {
        return $this->targetCashbookId;
    }
}
