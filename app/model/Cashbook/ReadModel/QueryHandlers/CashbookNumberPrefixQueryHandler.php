<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\Cashbook\Repositories\ICashbookRepository;

class CashbookNumberPrefixQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(CashbookNumberPrefixQuery $query): ?string
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        return $cashbook->getChitNumberPrefix();
    }

}
