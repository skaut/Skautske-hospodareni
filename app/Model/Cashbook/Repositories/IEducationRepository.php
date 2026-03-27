<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Education;
use App\Model\Event\SkautisEducationId;

interface IEducationRepository
{
    /** @throws CashbookNotFound */
    public function findBySkautisIdAndYear(SkautisEducationId $id, int $year): Education;

    /** @throws CashbookNotFound */
    public function findByCashbookId(CashbookId $cashbookId): Education;
}
