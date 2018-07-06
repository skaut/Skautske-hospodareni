<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\DTO\Cashbook\UnitCashbook;
use Model\Skautis\Mapper;

final class UnitCashbookListQueryHandler
{
    /** @var Mapper */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @return UnitCashbook[]
     */
    public function handle(UnitCashbookListQuery $query) : array
    {
        $cashbookId = $this->mapper->getLocalId($query->getUnitId(), ObjectType::UNIT);

        return [
            new UnitCashbook($cashbookId),
        ];
    }
}
