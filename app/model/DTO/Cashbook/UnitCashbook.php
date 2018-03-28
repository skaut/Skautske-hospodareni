<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

class UnitCashbook
{

    /** @var int */
    private $cashbookId;

    public function __construct(int $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

}
