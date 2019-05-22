<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\CategoryNotFound;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;

final class AddChitToCashbookHandler
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
     */
    public function __invoke(AddChitToCashbook $command) : void
    {
        $cashbook   = $this->cashbooks->find($command->getCashbookId());
        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        $cashbook->addChit($command->getBody(), $command->getPaymentMethod(), $command->getItems(), $categories);

        $this->cashbooks->save($cashbook);
    }
}
