<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;
use function assert;

class InconsistentCampCategoryTotalsQueryQueryHandler
{
    /** @var ICampCategoryRepository */
    private $campCategories;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(ICampCategoryRepository $campCategories, QueryBus $queryBus)
    {
        $this->campCategories = $campCategories;
        $this->queryBus       = $queryBus;
    }

    /**
     * @return float[]
     */
    public function __invoke(InconsistentCampCategoryTotalsQuery $query) : array
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($query->getCampId()));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $skautisTotals = [];

        foreach ($this->campCategories->findForCamp($query->getCampId()->toInt()) as $campCategory) {
            $id       = $campCategory->getId();
            $total    = $campCategory->getTotal();
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
