<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;

use function array_filter;

class CategoryPairsQueryHandler
{
    public function __construct(private CategoryRepository $categories, private ICashbookRepository $cashbooks)
    {
    }

    /**
     * @return string[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(CategoryPairsQuery $query): array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        if ($query->getOperationType() !== null) {
            $categories = array_filter(
                $categories,
                function (ICategory $category) use ($query): bool {
                    return $category->getOperationType()->equals($query->getOperationType());
                },
            );
        }

        $pairs = [];

        foreach ($categories as $category) {
            $pairs[$category->getId()] = $category->getName();
        }

        return $pairs;
    }
}
