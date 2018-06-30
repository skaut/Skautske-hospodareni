<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Category;
use Model\Utils\MoneyFactory;

class CategoryListQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var CategoryRepository */
    private $categories;

    public function __construct(ICashbookRepository $cashbooks, CategoryRepository $categories)
    {
        $this->cashbooks = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @return Category[]
     */
    public function handle(CategoryListQuery $query): array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        $categoryTotals = $cashbook->getCategoryTotals();

        return array_map(function (ICategory $category) use ($categoryTotals): Category {
            return new Category(
                $category->getId(),
                $category->getName(),
                MoneyFactory::fromFloat($categoryTotals[$category->getId()] ?? 0),
                $category->getShortcut(),
                $category->getOperationType(),
                $category->getOperationType()->equalsValue(Operation::INCOME)
            );
        }, $categories);
    }

}
