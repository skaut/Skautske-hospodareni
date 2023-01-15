<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Services\ICampCategoryUpdater;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\CategorySummary;
use Model\Utils\MoneyFactory;

use function assert;

class UpdateCampCategoryTotalHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private ICampCategoryUpdater $updater, private QueryBus $queryBus)
    {
    }

    public function __invoke(UpdateCampCategoryTotals $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $totals = [];
        foreach ($this->queryBus->handle(new CategoriesSummaryQuery($cashbook->getId())) as $category) {
            assert($category instanceof CategorySummary);
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        $this->updater->updateCategories(
            $cashbook->getId(),
            $totals,
        );
    }
}
