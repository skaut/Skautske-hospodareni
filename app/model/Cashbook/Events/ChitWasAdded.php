<?php

declare(strict_types=1);

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

final class ChitWasAdded
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $categoryId;

    public function __construct(CashbookId $cashbookId, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getCategoryId() : int
    {
        return $this->categoryId;
    }
}
