<?php

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook;
use Model\Cashbook\CashbookNotFoundException;

interface ICashbookRepository
{

    /**
     * @throws CashbookNotFoundException
     */
    public function find(int $id): Cashbook;

    public function save(Cashbook $cashbook): void;

}
