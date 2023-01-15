<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Cashbook;

class CashbookQueryHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    /** @throws CashbookNotFound */
    public function __invoke(CashbookQuery $query): Cashbook
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        return new Cashbook(
            $cashbook->getId(),
            $cashbook->getType(),
            $cashbook->getCashChitNumberPrefix(),
            $cashbook->getBankChitNumberPrefix(),
            $cashbook->getNote(),
            $cashbook->hasOnlyNumericChitNumbers(PaymentMethod::CASH()),
            $cashbook->hasOnlyNumericChitNumbers(PaymentMethod::BANK()),
        );
    }
}
