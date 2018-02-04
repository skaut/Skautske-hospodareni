<?php

namespace Model\Cashbook\Handlers\Cashbook;

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
        $this->cashbooks = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @throws \Model\Cashbook\CashbookNotFoundException
     * @throws \Model\Cashbook\CategoryNotFoundException
     * @throws \Model\Cashbook\ChitNotFoundException
     * @throws \Model\Cashbook\ChitLockedException
     */
    public function handle(UpdateChit $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $category = $this->categories->find($command->getCategoryId(), $cashbook->getId(), $cashbook->getType());

        $cashbook->updateChit(
            $command->getChitId(),
            $command->getNumber(),
            $command->getDate(),
            $command->getRecipient(),
            $command->getAmount(),
            $command->getPurpose(),
            $category
        );

        $this->cashbooks->save($cashbook);
    }

}
