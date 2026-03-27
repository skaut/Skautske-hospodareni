<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\ReadModel\CategoryTotalsCalculator;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;

use function array_filter;
use function in_array;

class CategoriesSummaryQueryHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private CategoryRepository $categories)
    {
    }

    /**
     * @return CategorySummary[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(CategoriesSummaryQuery $query): array
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
                $category->isVirtual(),
            );
        }

        return $categoriesSummaryById;
    }
}
