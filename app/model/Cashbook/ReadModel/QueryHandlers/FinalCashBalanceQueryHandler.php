<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Money;
use function array_map;
use function array_sum;

class FinalCashBalanceQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(FinalCashBalanceQuery $query) : Money
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId()));

        $balance = array_sum(array_map(function (Chit $chit) : float {
            return $chit->getSignedAmount();
        }, $chits));

        return MoneyFactory::fromFloat($balance);
    }
}
