<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ICategory;
use Model\Cashbook\ReadModel\CategoryTotalsCalculator;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;
use function array_filter;
use function in_array;

class CategoriesSummaryQueryHandler
{
    private ICashbookRepository $cashbooks;

    private CategoryRepository $categories;

    public function __construct(ICashbookRepository $cashbooks, CategoryRepository $categories)
    {
        $this->cashbooks  = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @return CategorySummary[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(CategoriesSummaryQuery $query) : array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        $calculator = new CategoryTotalsCalculator();

        $totalByCategories = $calculator->calculate($cashbook, $categories);

        // filter out camp refund categories
        $categories = array_filter($categories, function (ICategory $category) {
            return ! in_array($category->getId(), [ICategory::CATEGORY_REFUND_CHILD_ID, ICategory::CATEGORY_REFUND_ADULT_ID]);
        });

        $categoriesSummaryById = [];
        foreach ($categories as $category) {
            $categoriesSummaryById[$category->getId()] = new CategorySummary(
                $category->getId(),
                $category->getName(),
                MoneyFactory::fromFloat($totalByCategories[$category->getId()] ?? 0),
                $category->getOperationType(),
                $category->isVirtual()
            );
        }

        return $categoriesSummaryById;
    }
}
