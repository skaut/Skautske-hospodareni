<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit as ChitEntity;
use Nette\StaticClass;
use function count;

final class ChitFactory
{
    use StaticClass;

    /**
     * @param Category[] $categories
     */
    public static function create(ChitEntity $chit, array $categories) : Chit
    {
        /** @var ChitItem[] $items */
        $items = [];
        foreach ($chit->getItems() as $item) {
            $items[] = new ChitItem($item->getAmount(), $categories[$item->getCategory()->getId()], $item->getPurpose());
        }

        return new Chit(
            $chit->getId(),
            $chit->getBody(),
            $chit->isLocked(),
            count($items)=== 1 ? CashbookType::getInverseCashbookTypes($items[0]->getCategory()->getId()) : [],
            $chit->getPaymentMethod(),
            $items,
            $chit->getOperation(),
            $chit->getAmount()
        );
    }
}
