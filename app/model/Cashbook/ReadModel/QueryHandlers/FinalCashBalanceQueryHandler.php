<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Money;

use function array_map;
use function array_sum;

class FinalCashBalanceQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(FinalCashBalanceQuery $query): Money
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId()));

        $balance = array_sum(array_map(function (Chit $chit): float {
            return $chit->getSignedAmount();
        }, $chits));

        return MoneyFactory::fromFloat($balance);
    }
}
