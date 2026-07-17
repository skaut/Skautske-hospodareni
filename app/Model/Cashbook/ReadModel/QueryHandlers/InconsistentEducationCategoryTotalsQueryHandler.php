<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\InconsistentEducationCategoryTotalsQuery;
use App\Model\Cashbook\Repositories\IEducationCategoryRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;
use LogicException;

class InconsistentEducationCategoryTotalsQueryHandler
{
    public function __construct(private IEducationCategoryRepository $educationCategories, private QueryBus $queryBus)
    {
    }

    /** @return float[] */
    public function __invoke(InconsistentEducationCategoryTotalsQuery $query): array
    {
        $cashbookId = $this->queryBus->handle(new EducationCashbookIdQuery($query->getEducationId(), $query->getYear()));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $skautisTotals = [];

        foreach ($this->educationCategories->findForEducation($query->getEducationId()->toInt(), $query->getYear()) as $educationCategory) {
            $id = $educationCategory->getId();
            $total = $educationCategory->getTotal();
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
