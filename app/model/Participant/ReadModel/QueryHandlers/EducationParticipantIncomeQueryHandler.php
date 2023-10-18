<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Participant\Participant;

use function array_filter;
use function assert;

class EducationParticipantIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EducationParticipantIncomeQuery $query): float
    {
        $participants = array_filter(
            $this->queryBus->handle(new EducationParticipantListQuery($query->getEducationId())),
            static function (Participant $participant) {
                return $participant->isAccepted();
            },
        );

        $participantIncome = 0.0;
        foreach ($participants as $p) {
            assert($p instanceof Participant);
            $participantIncome += $p->getPayment();
        }

        return $participantIncome;
    }
}
