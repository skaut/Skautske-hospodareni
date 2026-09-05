<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Utils\MoneyFactory;
use App\Model\Participant\ZeroParticipantIncome;
use LogicException;
use Money\Money;

use function preg_match;

class CampParticipantIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(CampParticipantIncomeQuery $query): Money
    {
        $res = MoneyFactory::zero();
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

            $res = $res->add($p->getPayment());
        }

        if ($res->isZero()) {
            throw new ZeroParticipantIncome();
        }

        return $res;
    }
}
