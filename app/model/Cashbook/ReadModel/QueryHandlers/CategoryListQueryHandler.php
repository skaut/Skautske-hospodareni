<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Category;

class CategoryListQueryHandler
{
    private ICashbookRepository $cashbooks;

    private CategoryRepository $categories;

    public function __construct(ICashbookRepository $cashbooks, CategoryRepository $categories)
    {
        $this->cashbooks  = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @return Category[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(CategoryListQuery $query) : array
    {
        $cashbook   = $this->cashbooks->find($query->getCashbookId());
        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = new Category(
                $category->getId(),
                $category->getName(),
                $category->getShortcut(),
                $category->getOperationType(),
                $category->isVirtual()
            );
        }

        return $categoriesById;
    }
}
