<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;

/**
 * @see UpdateChitHandler
 */
final class UpdateChit
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var ChitBody */
    private $body;

    /** @var int */
    private $categoryId;

    public function __construct(CashbookId $cashbookId, int $chitId, ChitBody $body, int $categoryId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId     = $chitId;
        $this->body       = $body;
        $this->categoryId = $categoryId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
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
