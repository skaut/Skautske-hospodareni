<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ICategory;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use function array_filter;

class CategoryPairsQueryHandler
{
    /** @var CategoryRepository */
    private $categories;

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(CategoryRepository $categories, ICashbookRepository $cashbooks)
    {
        $this->categories = $categories;
        $this->cashbooks  = $cashbooks;
    }

    /**
     * @return string[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(CategoryPairsQuery $query) : array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        if ($query->getOperationType() !== null) {
            $categories = array_filter(
                $categories,
                function (ICategory $category) use ($query) : bool {
                    return $category->getOperationType()->equals($query->getOperationType());
                }
            );
        }

        $pairs = [];

        foreach ($categories as $category) {
            $pairs[$category->getId()] = $category->getName();
        }

        return $pairs;
    }
}
