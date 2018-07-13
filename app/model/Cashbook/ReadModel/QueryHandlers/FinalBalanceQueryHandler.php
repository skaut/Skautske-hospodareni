<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\FinalBalanceQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Utils\MoneyFactory;
use Money\Money;

class FinalBalanceQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(FinalBalanceQuery $query) : Money
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $balance = 0;

        foreach ($cashbook->getChits() as $chit) {
            $balance += $this->getSignedChitAmount($chit);
        }

        return MoneyFactory::fromFloat($balance);
    }

    private function getSignedChitAmount(Chit $chit) : float
    {
        $amount = $chit->getAmount()->getValue();

        if ($chit->getCategory()->getOperationType()->equalsValue(Operation::EXPENSE)) {
            return -1 * $amount;
        }

        return $amount;
    }
}
