<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\Repositories\IEducationRepository;

final class EducationCashbookIdQueryHandler
{
    private IEducationRepository $eventRepository;

    public function __construct(IEducationRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function __invoke(EducationCashbookIdQuery $query): CashbookId
    {
        return $this->eventRepository->findBySkautisId($query->getEducationId())->getCashbookId();
    }
}
