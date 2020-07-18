<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Skautis\Mapper;

final class CampCashbookIdQueryHandler
{
    private Mapper $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function __invoke(CampCashbookIdQuery $query) : CashbookId
    {
        return $this->mapper->getLocalId($query->getCampId()->toInt(), ObjectType::CAMP);
    }
}
