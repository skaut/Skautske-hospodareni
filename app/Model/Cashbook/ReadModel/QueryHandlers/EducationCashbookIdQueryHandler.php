<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\Repositories\IEducationRepository;

final class EducationCashbookIdQueryHandler
{
    public function __construct(private IEducationRepository $eventRepository)
    {
    }

    public function __invoke(EducationCashbookIdQuery $query): CashbookId
    {
        return $this->eventRepository->findBySkautisIdAndYear($query->getEducationId(), $query->getYear())->getCashbookId();
    }
}
