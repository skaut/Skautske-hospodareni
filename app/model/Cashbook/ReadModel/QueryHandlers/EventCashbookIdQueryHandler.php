<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Skautis\Mapper;

final class EventCashbookIdQueryHandler
{
    /** @var Mapper */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function handle(EventCashbookIdQuery $query) : CashbookId
    {
        return $this->mapper->getLocalId($query->getEventId()->toInt(), ObjectType::EVENT);
    }
}
