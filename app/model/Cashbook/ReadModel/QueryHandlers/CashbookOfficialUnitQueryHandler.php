<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Common\UnitId;
use Model\Event\Repositories\ICampRepository;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Payment\IUnitResolver;
use Model\Skautis\Mapper;
use Model\Unit\Unit;

class CashbookOfficialUnitQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var IEventRepository */
    private $eventRepository;

    /** @var ICampRepository */
    private $campRepository;

    /** @var IUnitRepository */
    private $unitRepository;

    /** @var Mapper */
    private $mapper;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(
        ICashbookRepository $cashbooks,
        ICampRepository $campRepository,
        IUnitRepository $unitRepository,
        Mapper $mapper,
        IUnitResolver $unitResolver
    ) {
        $this->cashbooks      = $cashbooks;
        $this->unitRepository = $unitRepository;
        $this->mapper         = $mapper;
        $this->campRepository = $campRepository;
        $this->unitRepository = $unitResolver;
    }

    /**
     * @throws CashbookNotFound
     */
    public function __invoke(CashbookOfficialUnitQuery $query) : Unit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        switch ($cashbook->getType()->getValue()) {
            case CashbookType::EVENT:
                $eventId = new SkautisEventId($this->mapper->getSkautisId($query->getCashbookId(), ObjectType::EVENT));
                $event   = $this->eventRepository->find($eventId);
                $unitId  = $event->getUnitId();
                break;
            case CashbookType::CAMP:
                $campId = new SkautisCampId($this->mapper->getSkautisId($query->getCashbookId(), ObjectType::CAMP));
                $camp   = $this->campRepository->find(new $campId());
                $unitId = $camp->getUnitId();
                break;
            default:
                $unit   = $this->unitRepository->findByCashbookId($query->getCashbookId());
                $unitId = $unit->getId()->toInt();
                break;
        }
        $officialUnitId = $this->unitResolver->getOfficialUnitId($unitId);

        return $this->unitRepository->find(new UnitId($officialUnitId));
    }
}
