<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;

/**
 * @see CreateCashbookHandler
 */
final class CreateCashbook
{

    /** @var int */
    private $id;

    /** @var CashbookType */
    private $type;

    public function __construct(int $id, CashbookType $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): CashbookType
    {
        return $this->type;
    }

}
