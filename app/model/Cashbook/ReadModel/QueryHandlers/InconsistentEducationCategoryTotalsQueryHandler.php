<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentEducationCategoryTotalsQuery;
use Model\Cashbook\Repositories\IEducationCategoryRepository;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;

use function assert;

class InconsistentEducationCategoryTotalsQueryHandler
{
    public function __construct(private IEducationCategoryRepository $educationCategories, private QueryBus $queryBus)
    {
    }

    /** @return float[] */
    public function __invoke(InconsistentEducationCategoryTotalsQuery $query): array
    {
        $cashbookId = $this->queryBus->handle(new EducationCashbookIdQuery($query->getEducationId()));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $skautisTotals = [];

        foreach ($this->educationCategories->findForEducation($query->getEducationId()->toInt()) as $educationCategory) {
            $id       = $educationCategory->getId();
            $total    = $educationCategory->getTotal();
            $category = $categories[$id];

            assert($category instanceof CategorySummary);

            $isConsistent = $category->getTotal()->equals($total);

            if ($isConsistent) {
                continue;
            }

            $skautisTotals[$id] = MoneyFactory::toFloat($total);
        }

        return $skautisTotals;
    }
}
