<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFoundException;

interface ICashbookRepository
{
    /**
     * @throws CashbookNotFoundException
     */
    public function find(CashbookId $id) : Cashbook;

    public function save(Cashbook $cashbook) : void;
}
