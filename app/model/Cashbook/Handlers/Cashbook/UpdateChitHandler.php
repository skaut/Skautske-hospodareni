<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\CategoryNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\ChitNotFound;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateChitHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var CategoryRepository */
    private $categories;

    public function __construct(ICashbookRepository $cashbooks, CategoryRepository $categories)
    {
        $this->cashbooks  = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @throws CashbookNotFound
     * @throws CategoryNotFound
     * @throws ChitNotFound
     * @throws ChitLocked
     */
    public function __invoke(UpdateChit $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->updateChit($command->getChitId(), $command->getBody(), $command->getPaymentMethod(), $command->getItems());

        $this->cashbooks->save($cashbook);
    }
}
