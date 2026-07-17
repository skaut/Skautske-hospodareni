<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Cashbook\Services\ICampCategoryUpdater;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;
use LogicException;

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
            if (! $category instanceof CategorySummary) {
                throw new LogicException('Assertion failed.');
            }
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        $this->updater->updateCategories(
            $cashbook->getId(),
            $totals,
        );
    }
}
