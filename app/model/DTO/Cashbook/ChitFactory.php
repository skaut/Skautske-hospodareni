<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit as ChitEntity;
use Model\Cashbook\MissingCategory;
use Nette\StaticClass;
use function array_key_exists;
use function count;
use function sprintf;

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
            if (! array_key_exists($item->getCategory()->getId(), $categories)) {
                throw new MissingCategory(sprintf("Kategorie dokladu '%d' nebyla nalezena!", $item->getCategory()->getId()));
            }
            $category = $categories[$item->getCategory()->getId()];

            $items[] = new ChitItem($item->getAmount(), $category, $item->getPurpose());
        }

        return new Chit(
            $chit->getId(),
            $chit->getBody(),
            $chit->isLocked(),
            count($items)=== 1 ? CashbookType::getInverseCashbookTypes($items[0]->getCategory()->getId()) : [],
            $chit->getPaymentMethod(),
            $items,
            $chit->getOperation(),
            $chit->getAmount(),
            $chit->getScans()
        );
    }
}
