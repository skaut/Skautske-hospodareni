<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Utils\MoneyFactory;
use Money\Money;
use function array_filter;
use function array_map;
use function array_sum;

class FinalRealBalanceQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function handle(FinalRealBalanceQuery $query) : Money
    {
        $chits   = $this->queryBus->handle(ChitListQuery::all($query->getCashbookId()));
        $chits   = array_filter($chits, function (Chit $chit) : bool {
            return ! $chit->getCategory()->isVirtual();
        });
        $balance = array_sum(array_map(function (Chit $chit) : float {
            return $chit->getSignedAmount();
        }, $chits));

        return MoneyFactory::fromFloat($balance);
    }
}
