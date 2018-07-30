<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;

/**
 * @see AddChitToCashbookHandler
 */
final class AddChitToCashbook
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var ChitBody */
    private $body;

    /** @var int */
    private $categoryId;

    public function __construct(CashbookId $cashbookId, ChitBody $body, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->body       = $body;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getBody() : ChitBody
    {
        return $this->body;
    }

    public function getCategoryId() : int
    {
        return $this->categoryId;
    }
}
