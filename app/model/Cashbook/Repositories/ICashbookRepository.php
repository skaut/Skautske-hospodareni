<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFound;

interface ICashbookRepository
{
    /**
     * @throws CashbookNotFound
     */
    public function find(CashbookId $id) : Cashbook;

    public function save(Cashbook $cashbook) : void;
}
