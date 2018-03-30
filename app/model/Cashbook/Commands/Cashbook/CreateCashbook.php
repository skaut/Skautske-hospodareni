<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;

/**
 * @see CreateCashbookHandler
 */
final class CreateCashbook
{

    /** @var CashbookId */
    private $id;

    /** @var CashbookType */
    private $type;

    public function __construct(CashbookId $id, CashbookType $type)
    {
        $this->id = $id;
        $this->type = $type;
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
