<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;
use Money\Money;

use function array_filter;
use function array_map;
use function array_sum;

class FinalRealBalanceQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(FinalRealBalanceQuery $query): Money
    {
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($query->getCashbookId()));

        $categories = array_filter($categories, function (CategorySummary $categorySummary): bool {
            return ! $categorySummary->isVirtual();
        });

        $balance = array_sum(array_map(function (CategorySummary $categorySummary): float {
            return ($categorySummary->isIncome() ? 1 : -1) * MoneyFactory::toFloat($categorySummary->getTotal());
        }, $categories));

        return MoneyFactory::fromFloat($balance);
    }
}
