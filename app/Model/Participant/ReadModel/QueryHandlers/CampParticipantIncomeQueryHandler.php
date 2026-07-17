<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Participant\ZeroParticipantIncome;
use LogicException;

use function preg_match;

class CampParticipantIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(CampParticipantIncomeQuery $query): Amount
    {
        $res = 0.0;
        $participants = $this->queryBus->handle(new CampParticipantListQuery($query->getCampId()));
        foreach ($participants as $p) {
            if (! $p instanceof Participant) {
                throw new LogicException('Assertion failed.');
            }
            // pokud se alespon v jednom neshodují, tak pokracujte
            if (
                ($query->isAdult() !== null && ($query->isAdult() xor preg_match('/^Dospěl/', $p->getCategory())))
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
