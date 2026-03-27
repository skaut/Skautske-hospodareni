<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\UpdateEducationCategoryTotals;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Cashbook\Services\IEducationCategoryUpdater;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\Utils\MoneyFactory;

use function assert;

class UpdateEducationCategoryTotalHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private IEducationCategoryUpdater $updater, private QueryBus $queryBus)
    {
    }

    public function __invoke(UpdateEducationCategoryTotals $command): void
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
