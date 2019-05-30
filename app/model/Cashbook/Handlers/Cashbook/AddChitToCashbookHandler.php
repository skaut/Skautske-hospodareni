<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook\Category;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\ChitItem as ChitItemDTO;
use function array_map;

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

        $items = array_map(function (ChitItemDTO $item) : ChitItem {
            $category = new Category($item->getCategory()->getId(), $item->getCategory()->getOperationType());

            return new ChitItem($item->getAmount(), $category, $item->getPurpose());
        }, $command->getItems());

        $categories = $this->categories->findForCashbook($cashbook->getId(), $cashbook->getType());

        $cashbook->addChit($command->getBody(), $command->getPaymentMethod(), $items, $categories);

        $this->cashbooks->save($cashbook);
    }
}
