<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\SkautisEducationYearQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Repositories\IEducationRepository;
use Model\Common\ShouldNotHappen;

class SkautisEducationYearQueryHandler
{
    public function __construct(
        private ICashbookRepository $cashbooks,
        private IEducationRepository $educationRepository,
    ) {
    }

    /**
     * @throws CashbookNotFound
     * @throws UnitNotFound
     */
    public function __invoke(SkautisEducationYearQuery $query): int
    {
        $cashbook   = $this->cashbooks->find($query->getCashbookId());
        $objectType = $cashbook->getType()->getSkautisObjectType();

        if (! $objectType->equalsValue(ObjectType::EDUCATION)) {
            throw new ShouldNotHappen();
        }

        return $this->educationRepository->findByCashbookId($query->getCashbookId())->getYear();
    }
}
