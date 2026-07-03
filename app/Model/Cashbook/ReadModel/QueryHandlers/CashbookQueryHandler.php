<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\DTO\Cashbook\Cashbook;

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
