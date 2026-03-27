<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Exception\UnitNotFound;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use App\Model\Cashbook\Repositories\ICampRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Cashbook\Repositories\IEducationRepository;
use App\Model\Cashbook\Repositories\IEventRepository;
use App\Model\Cashbook\Repositories\IUnitRepository;
use App\Model\Common\ShouldNotHappen;

class SkautisIdQueryHandler
{
    public function __construct(
        private ICashbookRepository $cashbooks,
        private IUnitRepository $units,
        private IEventRepository $eventRepository,
        private ICampRepository $campRepository,
        private IEducationRepository $educationRepository,
    ) {
    }

    /**
     * @throws CashbookNotFound
     * @throws UnitNotFound
     */
    public function __invoke(SkautisIdQuery $query): int
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
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

        if ($objectType->equalsValue(ObjectType::EDUCATION)) {
            return $this->educationRepository->findByCashbookId($query->getCashbookId())->getSkautisId()->toInt();
        }

        throw new ShouldNotHappen();
    }
}
