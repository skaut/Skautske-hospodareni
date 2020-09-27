<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\CategorySummary;
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

    public function __invoke(FinalRealBalanceQuery $query) : Money
    {
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($query->getCashbookId()));

        $categories = array_filter($categories, function (CategorySummary $categorySummary) : bool {
            return ! $categorySummary->isVirtual();
        });

        $balance = array_sum(array_map(function (CategorySummary $categorySummary) : float {
            return ($categorySummary->isIncome() ? 1 : -1) * MoneyFactory::toFloat($categorySummary->getTotal());
        }, $categories));

        return MoneyFactory::fromFloat($balance);
    }
}
