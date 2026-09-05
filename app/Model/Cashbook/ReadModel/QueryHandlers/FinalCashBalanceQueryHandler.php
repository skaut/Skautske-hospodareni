<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\Utils\MoneyFactory;
use Money\Money;

class FinalCashBalanceQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(FinalCashBalanceQuery $query): Money
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId()));

        $total = MoneyFactory::zero();
        foreach ($chits as $chit) {
            $total = $total->add($chit->getSignedAmount());
        }

        return $total;
    }
}
