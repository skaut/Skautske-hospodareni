<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Event;
use App\Model\Event\SkautisEventId;

interface IEventRepository
{
    /** @throws CashbookNotFound */
    public function findBySkautisId(SkautisEventId $id): Event;

    /** @throws CashbookNotFound */
    public function findByCashbookId(CashbookId $cashbookId): Event;
}
