<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use App\Model\Cashbook\Repositories\ICampCategoryRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;
use LogicException;

class InconsistentCampCategoryTotalsQueryQueryHandler
{
    public function __construct(private ICampCategoryRepository $campCategories, private QueryBus $queryBus)
    {
    }

    /** @return float[] */
    public function __invoke(InconsistentCampCategoryTotalsQuery $query): array
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($query->getCampId()));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $skautisTotals = [];

        foreach ($this->campCategories->findForCamp($query->getCampId()->toInt()) as $campCategory) {
            $id = $campCategory->getId();
            $total = $campCategory->getTotal();
            $category = $categories[$id];

            if (! $category instanceof CategorySummary) {
                throw new LogicException('Assertion failed.');
            }
            $isConsistent = $category->getTotal()->equals($total);

            if ($isConsistent) {
                continue;
            }

            $skautisTotals[$id] = MoneyFactory::toFloat($total);
        }

        return $skautisTotals;
    }
}
