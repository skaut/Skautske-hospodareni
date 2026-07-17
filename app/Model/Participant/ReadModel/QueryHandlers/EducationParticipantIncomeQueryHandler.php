<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use LogicException;

class EducationParticipantIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EducationParticipantIncomeQuery $query): float
    {
        $participants = $this->queryBus->handle(new EducationParticipantListQuery($query->getEducationId()));

        $participantIncome = 0.0;
        foreach ($participants as $p) {
            if (! $p instanceof Participant) {
                throw new LogicException('Assertion failed.');
            }
            $participantIncome += $p->getPayment();
        }

        return $participantIncome;
    }
}
