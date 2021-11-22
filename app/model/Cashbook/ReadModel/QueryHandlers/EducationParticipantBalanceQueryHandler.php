<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationParticipantBalanceQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use Model\Common\Services\QueryBus;

class EducationParticipantBalanceQueryHandler
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(EducationParticipantBalanceQuery $query): float
    {
        $participantIncome = $this->queryBus->handle(new EducationParticipantIncomeQuery($query->getEducationId()));
        $chitSum           = $this->queryBus->handle(new ParticipantChitSumQuery($query->getCashbookId()));

        return $participantIncome - $chitSum;
    }
}
