<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\Participant\ZeroParticipantIncome;
use function assert;
use function preg_match;

class CampParticipantIncomeQueryHandler
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(CampParticipantIncomeQuery $query) : Amount
    {
        $res          = 0.0;
        $participants = $this->queryBus->handle(new CampParticipantListQuery($query->getCampId()));
        foreach ($participants as $p) {
            assert($p instanceof Participant);
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($query->isAdult() !== null && ($query->isAdult() xor preg_match('/^Dospěl/', $p->getCategory())))
                || ($query->isOnAccount() !== null && ($query->isOnAccount() xor $p->getOnAccount() === 'Y'))
            ) {
                continue;
            }

            $res += $p->getPayment();
        }

        if ($res === 0.0) {
            throw new ZeroParticipantIncome();
        }

        return Amount::fromFloat($res);
    }
}
