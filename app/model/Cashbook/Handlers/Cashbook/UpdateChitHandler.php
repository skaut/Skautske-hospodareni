<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook\Category;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\ChitNotFound;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\ChitItem as ChitItemDTO;
use function array_map;

final class UpdateChitHandler
{
    private ICashbookRepository $cashbooks;

    private CategoryRepository $categories;

    public function __construct(ICashbookRepository $cashbooks, CategoryRepository $categories)
    {
        $this->cashbooks  = $cashbooks;
        $this->categories = $categories;
    }

    /**
     * @throws CashbookNotFound
     * @throws ChitNotFound
     * @throws ChitLocked
     */
    public function __invoke(UpdateChit $command) : void
    {
        $cashbook   = $this->cashbooks->find($command->getCashbookId());
        $categories = $this->categories->findForCashbook($command->getCashbookId(), $cashbook->getType());

        $items = array_map(function (ChitItemDTO $item) : ChitItem {
            $category = new Category($item->getCategory()->getId(), $item->getCategory()->getOperationType());

            return new ChitItem($item->getAmount(), $category, $item->getPurpose());
        }, $command->getItems());

        $cashbook->updateChit($command->getChitId(), $command->getBody(), $command->getPaymentMethod(), $items, $categories);

        $this->cashbooks->save($cashbook);
    }
}
