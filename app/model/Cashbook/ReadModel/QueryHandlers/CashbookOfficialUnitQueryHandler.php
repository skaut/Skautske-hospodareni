<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\Repositories\ICampRepository as ICashbookCampRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IEventRepository as ICashbookEventRepository;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Common\ShouldNotHappen;
use Model\Common\UnitId;
use Model\Event\Repositories\ICampRepository;
use Model\Event\Repositories\IEventRepository;
use Model\Payment\IUnitResolver;
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

    private IUnitResolver $unitResolver;

    private ICashbookEventRepository $eventCashbookRepository;

    private ICashbookCampRepository $campCashbookRepository;

    public function __construct(
        ICashbookRepository $cashbooks,
        IEventRepository $eventRepository,
        ICampRepository $campRepository,
        IUnitRepository $unitRepository,
        IUnitResolver $unitResolver,
        ISkautisUnitRepository $skautisUnitRepository,
        ICashbookEventRepository $eventCashbookRepository,
        ICashbookCampRepository $campCashbookRepository
    ) {
        $this->cashbooks               = $cashbooks;
        $this->eventRepository         = $eventRepository;
        $this->unitRepository          = $unitRepository;
        $this->campRepository          = $campRepository;
        $this->unitResolver            = $unitResolver;
        $this->skautisUnitRepository   = $skautisUnitRepository;
        $this->eventCashbookRepository = $eventCashbookRepository;
        $this->campCashbookRepository  = $campCashbookRepository;
    }

    /**
     * @throws CashbookNotFound
     */
    public function __invoke(CashbookOfficialUnitQuery $query): Unit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        $unitId   = $this->resolveUnitThatOwnsCashbook($cashbook);

        return $this->skautisUnitRepository->find($this->unitResolver->getOfficialUnitId($unitId->toInt()));
    }

    private function resolveUnitThatOwnsCashbook(Cashbook $cashbook): UnitId
    {
        if ($cashbook->getType()->equalsValue(CashbookType::EVENT)) {
            $eventId = $this->eventCashbookRepository->findByCashbookId($cashbook->getId())->getSkautisId();

            return $this->eventRepository->find($eventId)->getUnitId();
        }

        if ($cashbook->getType()->equalsValue(CashbookType::CAMP)) {
            $campId = $this->campCashbookRepository->findByCashbookId($cashbook->getId())->getSkautisId();

            return $this->campRepository->find($campId)->getUnitId();
        }

        if ($cashbook->getType()->isUnit()) {
            return $this->unitRepository->findByCashbookId($cashbook->getId())->getId();
        }

        throw new ShouldNotHappen(sprintf('Unknown cashbook type "%s"', $cashbook->getType()->getValue()));
    }
}
