<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\CategoryCatalog;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;

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

        if ($query->getOperationType() === null) {
            return CategoryCatalog::selectablePairs($categories, Operation::INCOME())
                + CategoryCatalog::selectablePairs($categories, Operation::EXPENSE());
        }

        return CategoryCatalog::selectablePairs($categories, $query->getOperationType());
    }
}
