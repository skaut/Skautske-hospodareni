<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Exception\UnitNotFound;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\SkautisEducationYearQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Cashbook\Repositories\IEducationRepository;
use App\Model\Common\ShouldNotHappen;

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
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        $objectType = $cashbook->getType()->getSkautisObjectType();

        if (! $objectType->equalsValue(ObjectType::EDUCATION)) {
            throw new ShouldNotHappen();
        }

        return $this->educationRepository->findByCashbookId($query->getCashbookId())->getYear();
    }
}
