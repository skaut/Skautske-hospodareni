<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Camp;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Event\SkautisCampId;

interface ICampRepository
{
    /** @throws CashbookNotFound */
    public function findBySkautisId(SkautisCampId $id): Camp;

    /** @throws CashbookNotFound */
    public function findByCashbookId(CashbookId $cashbookId): Camp;

    // public function save(Cashbook $cashbook): void;
}
