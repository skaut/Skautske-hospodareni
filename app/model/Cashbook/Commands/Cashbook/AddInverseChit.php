<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\AddInverseChitHandler;

/**
 * @see AddInverseChitHandler
 */
final class AddInverseChit
{
    /** @var int */
    private $originaliCashbookId;

    /** @var int */
    private $targetCashbookId;

    /** @var int */
    private $chitId;

    public function __construct(int $originalCashbookId, int $targetCashbookId, int $chitId)
    {
        $this->originaliCashbookId = $originalCashbookId;
        $this->targetCashbookId = $targetCashbookId;
        $this->chitId = $chitId;
    }

    public function getOriginalCashbookId(): int
    {
        return $this->originaliCashbookId;
    }

    public function getTargetCashbookId(): int
    {
        return $this->targetCashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

}
