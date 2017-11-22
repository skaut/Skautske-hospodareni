<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Cashbook\Services\ICampCategoryUpdater;

class UpdateCampCategoryTotalHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var ICampCategoryUpdater */
    private $updater;

    public function __construct(ICashbookRepository $cashbooks, ICampCategoryUpdater $updater)
    {
        $this->cashbooks = $cashbooks;
        $this->updater = $updater;
    }

    public function handle(UpdateCampCategoryTotal $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $categoryId = $command->getCategoryId();
        $totals = $cashbook->getCategoryTotals();

        if(!isset($totals[$categoryId])) {
            return;
        }

        $this->updater->updateCategories(
            $cashbook->getId(),
            [ $categoryId => $totals[$categoryId] ]
        );
    }

}
