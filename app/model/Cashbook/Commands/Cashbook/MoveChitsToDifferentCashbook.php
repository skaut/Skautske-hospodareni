<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\MoveChitsToDifferentCashbookHandler;

/**
 * @see MoveChitsToDifferentCashbookHandler
 */
final class MoveChitsToDifferentCashbook
{
    /** @var int[] */
    private $chitIds;

    /** @var CashbookId */
    private $sourceCashbookId;

    /** @var CashbookId */
    private $targetCashbookId;

    /**
     * @param int[] $chitIds
     */
    public function __construct(array $chitIds, CashbookId $sourceCashbookId, CashbookId $targetCashbookId)
    {
        $this->chitIds          = $chitIds;
        $this->sourceCashbookId = $sourceCashbookId;
        $this->targetCashbookId = $targetCashbookId;
    }

    /**
     * @return int[]
     */
    public function getChitIds() : array
    {
        return $this->chitIds;
    }

    public function getSourceCashbookId() : CashbookId
    {
        return $this->sourceCashbookId;
    }

    public function getTargetCashbookId() : CashbookId
    {
        return $this->targetCashbookId;
    }
}
