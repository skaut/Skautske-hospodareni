<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\RemoveChitFromCashbookHandler;

/**
 * @see RemoveChitFromCashbookHandler
 */
final class RemoveChitFromCashbook
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $chitId;

    public function __construct(int $cashbookId, int $chitId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId = $chitId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

}
