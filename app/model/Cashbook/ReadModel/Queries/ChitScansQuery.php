<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;

/** @see ChitScansQueryHandler */
final class ChitScansQuery
{
    public function __construct(private CashbookId $cashbookId, private int $chitId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }
}
