<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Category;
use Model\Utils\MoneyFactory;
use function assert;

class InconsistentCampCategoryTotalsQueryQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var ICampCategoryRepository */
    private $campCategories;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(ICashbookRepository $cashbooks, ICampCategoryRepository $campCategories, QueryBus $queryBus)
    {
        $this->cashbooks      = $cashbooks;
        $this->campCategories = $campCategories;
        $this->queryBus       = $queryBus;
    }

    /**
     * @return float[]
     */
    public function __invoke(InconsistentCampCategoryTotalsQuery $query) : array
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($query->getCampId()));
        $categories = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        $skautisTotals = [];

        foreach ($this->campCategories->findForCamp($query->getCampId()->toInt()) as $campCategory) {
            $id       = $campCategory->getId();
            $total    = $campCategory->getTotal();
            $category = $categories[$id];

            assert($category instanceof Category);

            $isConsistent = $category->getTotal()->equals($total);

            if ($isConsistent) {
                continue;
            }

            $skautisTotals[$id] = MoneyFactory::toFloat($total);
        }

        return $skautisTotals;
    }
}
