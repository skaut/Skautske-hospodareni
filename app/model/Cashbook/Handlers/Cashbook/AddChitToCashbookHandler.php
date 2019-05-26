<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFound;
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
     */
    public function __invoke(AddChitToCashbook $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->addChit($command->getBody(), $command->getPaymentMethod(), $command->getItems());

        $this->cashbooks->save($cashbook);
    }
}
