<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\Repositories\ICampRepository;

final class CampCashbookIdQueryHandler
{
    public function __construct(private ICampRepository $campRepository)
    {
    }

    public function __invoke(CampCashbookIdQuery $query): CashbookId
    {
        return $this->campRepository->findBySkautisId($query->getCampId())->getCashbookId();
    }
}
