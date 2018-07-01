<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Cashbook;

class CashbookQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    /**
     * @throws \Model\Cashbook\CashbookNotFoundException
     */
    public function handle(CashbookQuery $query): Cashbook
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        return new Cashbook($cashbook->getId(), $cashbook->getType(), $cashbook->getChitNumberPrefix(), $cashbook->getNote());
    }

}
