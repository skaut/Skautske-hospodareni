<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Skautis\Mapper;

class SkautisIdQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var IUnitRepository */
    private $units;

    /** @var Mapper */
    private $mapper;

    public function __construct(ICashbookRepository $cashbooks, IUnitRepository $units, Mapper $mapper)
    {
        $this->cashbooks = $cashbooks;
        $this->units     = $units;
        $this->mapper    = $mapper;
    }

    /**
     * @throws CashbookNotFound
     * @throws UnitNotFound
     */
    public function __invoke(SkautisIdQuery $query) : int
    {
        $cashbook   = $this->cashbooks->find($query->getCashbookId());
        $objectType = $cashbook->getType()->getSkautisObjectType();

        if ($objectType->equalsValue(ObjectType::UNIT)) {
            return $this->units->findByCashbookId($query->getCashbookId())->getId()->toInt();
        }

        return $this->mapper->getSkautisId($cashbook->getId(), $objectType->toString());
    }
}
