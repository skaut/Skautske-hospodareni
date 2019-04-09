<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit as ChitEntity;
use Nette\StaticClass;

final class ChitFactory
{
    use StaticClass;

    /**
     * @param Category[] $categories
     */
    public static function create(ChitEntity $chit, array $categories) : Chit
    {
        $items = [];
        foreach ($chit->getItems() as $item) {
            $items[] = new ChitItem($item->getId(), $item->getAmount(), $categories[$item->getCategory()->getId()]);
        }

        return new Chit(
            $chit->getId(),
            $chit->getBody(),
            $chit->isLocked(),
            CashbookType::getInverseCashbookTypes($chit->getCategoryId()),
            $chit->getPaymentMethod(),
            $items,
            $chit->getOperation(),
            $chit->getAmount()
        );
    }
}
