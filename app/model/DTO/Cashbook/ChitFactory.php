<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\Chit as ChitEntity;
use Nette\StaticClass;

final class ChitFactory
{

    use StaticClass;

    public static function create(ChitEntity $chit): Chit
    {
        return new Chit(
            $chit->getId(),
            $chit->getNumber(),
            $chit->getDate(),
            $chit->getRecipient(),
            $chit->getAmount(),
            $chit->getPurpose(),
            $chit->getCategory(),
            $chit->isLocked()
        );
    }

}
