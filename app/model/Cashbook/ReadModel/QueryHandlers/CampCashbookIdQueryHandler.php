<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Skautis\Mapper;

final class CampCashbookIdQueryHandler
{
    /** @var Mapper */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function handle(CampCashbookIdQuery $query) : CashbookId
    {
        return $this->mapper->getLocalId($query->getCampId()->toInt(), ObjectType::CAMP);
    }
}
