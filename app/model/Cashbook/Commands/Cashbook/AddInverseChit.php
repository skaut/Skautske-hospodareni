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
    /** @var CashbookId */
    private $originaliCashbookId;

    /** @var CashbookId */
    private $targetCashbookId;

    /** @var int */
    private $chitId;

    public function __construct(CashbookId $originalCashbookId, CashbookId $targetCashbookId, int $chitId)
    {
        $this->originaliCashbookId = $originalCashbookId;
        $this->targetCashbookId    = $targetCashbookId;
        $this->chitId              = $chitId;
    }

    public function getOriginalCashbookId() : CashbookId
    {
        return $this->originaliCashbookId;
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
