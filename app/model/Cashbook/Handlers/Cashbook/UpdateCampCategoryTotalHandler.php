<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
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
        $this->updater   = $updater;
    }

    public function handle(UpdateCampCategoryTotals $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $this->updater->updateCategories(
            $cashbook->getId(),
            $cashbook->getCategoryTotals()
        );
    }
}
