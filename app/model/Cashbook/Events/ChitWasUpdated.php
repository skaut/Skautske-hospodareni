<?php

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

final class ChitWasUpdated
{

    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $oldCategoryId;

    /** @var int */
    private $newCategoryId;

    public function __construct(CashbookId $cashbookId, int $oldCategoryId, int $newCategoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->oldCategoryId = $oldCategoryId;
        $this->newCategoryId = $newCategoryId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getOldCategoryId(): int
    {
        return $this->oldCategoryId;
    }

    public function getNewCategoryId(): int
    {
        return $this->newCategoryId;
    }

}
