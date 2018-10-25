<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Money;

class FinalCashBalanceQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function handle(FinalCashBalanceQuery $query) : Money
    {
        $chits   = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId()));
        $balance = 0;

        foreach ($chits as $chit) {
            $balance += $this->getSignedChitAmount($chit);
        }

        return MoneyFactory::fromFloat($balance);
    }

    private function getSignedChitAmount(Chit $chit) : float
    {
        $amount = $chit->getBody()->getAmount()->toFloat();

        if ($chit->getCategory()->getOperationType()->equalsValue(Operation::EXPENSE)) {
            return -1 * $amount;
        }

        return $amount;
    }
}
