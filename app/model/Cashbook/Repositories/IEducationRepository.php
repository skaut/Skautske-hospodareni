<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Education;
use Model\Event\SkautisEducationId;

interface IEducationRepository
{
    /** @throws CashbookNotFound */
    public function findBySkautisIdAndYear(SkautisEducationId $id, int $year): Education;

    /** @throws CashbookNotFound */
    public function findByCashbookId(CashbookId $cashbookId): Education;
}
