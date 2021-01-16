<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Cashbook\Repositories\ICampRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IEventRepository;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Common\ShouldNotHappen;

class SkautisIdQueryHandler
{
    private ICashbookRepository $cashbooks;

    private IUnitRepository $units;

    private IEventRepository $eventRepository;

    private ICampRepository $campRepository;

    public function __construct(
        ICashbookRepository $cashbooks,
        IUnitRepository $units,
        IEventRepository $eventRepository,
        ICampRepository $campRepository
    ) {
        $this->cashbooks       = $cashbooks;
        $this->units           = $units;
        $this->eventRepository = $eventRepository;
        $this->campRepository  = $campRepository;
    }

    /**
     * @throws CashbookNotFound
     * @throws UnitNotFound
     */
    public function __invoke(SkautisIdQuery $query): int
    {
        $cashbook   = $this->cashbooks->find($query->getCashbookId());
        $objectType = $cashbook->getType()->getSkautisObjectType();

        if ($objectType->equalsValue(ObjectType::UNIT)) {
            return $this->units->findByCashbookId($query->getCashbookId())->getId()->toInt();
        }

        if ($objectType->equalsValue(ObjectType::EVENT)) {
            return $this->eventRepository->findByCashbookId($query->getCashbookId())->getSkautisId()->toInt();
        }

        if ($objectType->equalsValue(ObjectType::CAMP)) {
            return $this->campRepository->findByCashbookId($query->getCashbookId())->getSkautisId()->toInt();
        }

        throw new ShouldNotHappen();
    }
}
