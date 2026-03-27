<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Cashbook\Category;
use App\Model\Cashbook\Cashbook\ChitItem;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ChitLocked;
use App\Model\Cashbook\ChitNotFound;
use App\Model\Cashbook\Commands\Cashbook\UpdateChit;
use App\Model\Cashbook\Repositories\CategoryRepository;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\DTO\Cashbook\ChitItem as ChitItemDTO;

use function array_map;

final class UpdateChitHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private CategoryRepository $categories)
    {
    }

    /**
     * @throws CashbookNotFound
     * @throws ChitNotFound
     * @throws ChitLocked
     */
    public function __invoke(UpdateChit $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $categories = $this->categories->findForCashbook($command->getCashbookId(), $cashbook->getType());

        $items = array_map(function (ChitItemDTO $item): ChitItem {
            $category = new Category($item->getCategory()->getId(), $item->getCategory()->getOperationType());

            return new ChitItem($item->getAmount(), $category, $item->getPurpose());
        }, $command->getItems());

        $cashbook->updateChit($command->getChitId(), $command->getBody(), $command->getPaymentMethod(), $items, $categories);

        $this->cashbooks->save($cashbook);
    }
}
