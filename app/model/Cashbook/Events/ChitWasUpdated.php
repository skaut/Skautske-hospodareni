<?php

namespace Model\Cashbook\Events;

final class ChitWasUpdated
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $oldCategoryId;

    /** @var int */
    private $newCategoryId;

    public function __construct(int $cashbookId, int $oldCategoryId, int $newCategoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->oldCategoryId = $oldCategoryId;
        $this->newCategoryId = $newCategoryId;
    }

    public function getCashbookId(): int
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
