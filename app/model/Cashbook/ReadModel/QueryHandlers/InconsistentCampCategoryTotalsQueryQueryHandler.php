<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Utils\MoneyFactory;

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
     *
     * @throws CashbookNotFound
     */
    public function __invoke(InconsistentCampCategoryTotalsQuery $query) : array
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($query->getCampId()));

        $cashbook = $this->cashbooks->find($cashbookId);
        $totals   = $cashbook->getCategoryTotals();

        $skautisTotals = [];

        foreach ($this->campCategories->findForCamp($query->getCampId()->toInt()) as $category) {
            $id           = $category->getId();
            $total        = $category->getTotal();
            $isConsistent = MoneyFactory::fromFloat($totals[$id] ?? 0)->equals($total);

            if ($isConsistent) {
                continue;
            }

            $skautisTotals[$id] = MoneyFactory::toFloat($total);
        }

        return $skautisTotals;
    }
}
