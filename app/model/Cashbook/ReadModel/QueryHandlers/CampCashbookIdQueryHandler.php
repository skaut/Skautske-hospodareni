<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\Repositories\ICampRepository;

final class CampCashbookIdQueryHandler
{
    private ICampRepository $campRepository;

    public function __construct(ICampRepository $campRepository)
    {
        $this->campRepository = $campRepository;
    }

    public function __invoke(CampCashbookIdQuery $query): CashbookId
    {
        return $this->campRepository->findBySkautisId($query->getCampId())->getCashbookId();
    }
}
