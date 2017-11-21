<?php

namespace Model\Cashbook\Events;

final class ChitWasAdded
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $categoryId;

    public function __construct(int $cashbookId, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

}
