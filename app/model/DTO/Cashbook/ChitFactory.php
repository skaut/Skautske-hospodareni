<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit as ChitEntity;
use Nette\StaticClass;

final class ChitFactory
{
    use StaticClass;

    public static function create(ChitEntity $chit, Category $category) : Chit
    {
        return new Chit(
            $chit->getId(),
            $chit->getBody(),
            $category,
            $chit->isLocked(),
            CashbookType::getInverseCashbookTypes($chit->getCategoryId()),
            $chit->getPaymentMethod()
        );
    }
}
