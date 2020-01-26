<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Services\ICampCategoryUpdater;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;
use function assert;

class UpdateCampCategoryTotalHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var ICampCategoryUpdater */
    private $updater;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(ICashbookRepository $cashbooks, ICampCategoryUpdater $updater, QueryBus $queryBus)
    {
        $this->cashbooks = $cashbooks;
        $this->updater   = $updater;
        $this->queryBus  = $queryBus;
    }

    public function __invoke(UpdateCampCategoryTotals $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $totals = [];
        foreach ($this->queryBus->handle(new CategoriesSummaryQuery($cashbook->getId())) as $category) {
            assert($category instanceof CategorySummary);
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        $this->updater->updateCategories(
            $cashbook->getId(),
            $totals
        );
    }
}
