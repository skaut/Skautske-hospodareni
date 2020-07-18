<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Common\ShouldNotHappen;
use Model\Common\UnitId;
use Model\Event\Repositories\ICampRepository;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Payment\IUnitResolver;
use Model\Skautis\Mapper;
use Model\Unit\Repositories\IUnitRepository as ISkautisUnitRepository;
use Model\Unit\Unit;
use function sprintf;

class CashbookOfficialUnitQueryHandler
{
    private ICashbookRepository $cashbooks;

    private IEventRepository $eventRepository;

    private ICampRepository $campRepository;

    private IUnitRepository $unitRepository;

    private ISkautisUnitRepository $skautisUnitRepository;

    private Mapper $mapper;

    private IUnitResolver $unitResolver;

    public function __construct(
        ICashbookRepository $cashbooks,
        IEventRepository $eventRepository,
        ICampRepository $campRepository,
        IUnitRepository $unitRepository,
        Mapper $mapper,
        IUnitResolver $unitResolver,
        ISkautisUnitRepository $skautisUnitRepository
    ) {
        $this->cashbooks             = $cashbooks;
        $this->eventRepository       = $eventRepository;
        $this->unitRepository        = $unitRepository;
        $this->mapper                = $mapper;
        $this->campRepository        = $campRepository;
        $this->unitResolver          = $unitResolver;
        $this->skautisUnitRepository = $skautisUnitRepository;
    }

    /**
     * @throws CashbookNotFound
     */
    public function __invoke(CashbookOfficialUnitQuery $query) : Unit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        $unitId   = $this->resolveUnitThatOwnsCashbook($cashbook);

        return $this->skautisUnitRepository->find($this->unitResolver->getOfficialUnitId($unitId->toInt()));
    }

    private function resolveUnitThatOwnsCashbook(Cashbook $cashbook) : UnitId
    {
        if ($cashbook->getType()->equalsValue(CashbookType::EVENT)) {
            $eventId = new SkautisEventId($this->mapper->getSkautisId($cashbook->getId(), ObjectType::EVENT));

            return $this->eventRepository->find($eventId)->getUnitId();
        }

        if ($cashbook->getType()->equalsValue(CashbookType::CAMP)) {
            $campId = new SkautisCampId($this->mapper->getSkautisId($cashbook->getId(), ObjectType::CAMP));

            return $this->campRepository->find($campId)->getUnitId();
        }

        if ($cashbook->getType()->isUnit()) {
            return $this->unitRepository->findByCashbookId($cashbook->getId())->getId();
        }

        throw new ShouldNotHappen(sprintf('Unknown cashbook type "%s"', $cashbook->getType()->getValue()));
    }
}
