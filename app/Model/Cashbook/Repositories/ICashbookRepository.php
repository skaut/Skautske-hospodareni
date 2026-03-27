<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\CashbookNotFound;

interface ICashbookRepository
{
    /** @throws CashbookNotFound */
    public function find(CashbookId $id): Cashbook;

    public function save(Cashbook $cashbook): void;
}
