<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ReadModel\Queries\CashbookTypeQuery;
use Model\Cashbook\Repositories\ICashbookRepository;

class CashbookTypeQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(CashbookTypeQuery $query): CashbookType
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        return $cashbook->getType();
    }

}
