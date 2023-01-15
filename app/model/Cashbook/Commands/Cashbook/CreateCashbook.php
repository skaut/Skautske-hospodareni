<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;

/** @see CreateCashbookHandler */
final class CreateCashbook
{
    public function __construct(private CashbookId $id, private CashbookType $type)
    {
    }

    public function getId(): CashbookId
    {
        return $this->id;
    }

    public function getType(): CashbookType
    {
        return $this->type;
    }
}
