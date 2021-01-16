<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Event;
use Model\Event\SkautisEventId;

interface IEventRepository
{
    /**
     * @throws CashbookNotFound
     */
    public function findBySkautisId(SkautisEventId $id): Event;

    /**
     * @throws CashbookNotFound
     */
    public function findByCashbookId(CashbookId $cashbookId): Event;
}
