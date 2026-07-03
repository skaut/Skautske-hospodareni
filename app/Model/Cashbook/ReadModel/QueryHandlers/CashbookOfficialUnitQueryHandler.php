<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use App\Model\Cashbook\Repositories\ICampRepository as ICashbookCampRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Cashbook\Repositories\IEducationRepository as ICashbookEducationRepository;
use App\Model\Cashbook\Repositories\IEventRepository as ICashbookEventRepository;
use App\Model\Cashbook\Repositories\IUnitRepository;
use App\Model\Common\ShouldNotHappen;
use App\Model\Common\UnitId;
use App\Model\Event\Repositories\ICampRepository;
use App\Model\Event\Repositories\IEducationRepository;
use App\Model\Event\Repositories\IEventRepository;
use App\Model\Payment\IUnitResolver;
use App\Model\Unit\Repositories\IUnitRepository as ISkautisUnitRepository;
use App\Model\Unit\Unit;

use function sprintf;

class CashbookOfficialUnitQueryHandler
{
    public function __construct(
        private ICashbookRepository $cashbooks,
        private IEventRepository $eventRepository,
        private ICampRepository $campRepository,
        private IEducationRepository $educationRepository,
        private IUnitRepository $unitRepository,
        private IUnitResolver $unitResolver,
        private ISkautisUnitRepository $skautisUnitRepository,
        private ICashbookEventRepository $eventCashbookRepository,
        private ICashbookCampRepository $campCashbookRepository,
        private ICashbookEducationRepository $educationCashbookRepository,
    ) {
    }

    /** @throws CashbookNotFound */
    public function __invoke(CashbookOfficialUnitQuery $query): Unit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        $unitId = $this->resolveUnitThatOwnsCashbook($cashbook);

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

        if ($cashbook->getType()->equalsValue(CashbookType::EDUCATION)) {
            $educationId = $this->educationCashbookRepository->findByCashbookId($cashbook->getId())->getSkautisId();

            return $this->educationRepository->find($educationId)->getUnitId();
        }

        if ($cashbook->getType()->isUnit()) {
            return $this->unitRepository->findByCashbookId($cashbook->getId())->getId();
        }

        throw new ShouldNotHappen(sprintf('Unknown cashbook type "%s"', $cashbook->getType()->getValue()));
    }
}
