<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;
use Money\Money;

use function array_filter;

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

        return array_reduce(
            $categories,
            fn (Money $total, CategorySummary $categorySummary): Money => $total->add(
                $categorySummary->isIncome() ? $categorySummary->getTotal() : $categorySummary->getTotal()->multiply(-1),
            ),
            MoneyFactory::zero(),
        );
    }
}
