<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFound;
use Model\Event\SkautisCampId;

interface ICampRepository
{
    /**
     * @throws CashbookNotFound
     */
    public function findBySkautisId(SkautisCampId $id): Camp;

    /**
     * @throws CashbookNotFound
     */
    public function findByCashbookId(CashbookId $cashbookId): Camp;

// public function save(Cashbook $cashbook): void;
}
