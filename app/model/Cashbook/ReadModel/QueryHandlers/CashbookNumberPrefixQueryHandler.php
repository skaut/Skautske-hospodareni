<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\EventTable;

class CashbookNumberPrefixQueryHandler
{

    /** @var EventTable */
    private $table;

    public function __construct(EventTable $table)
    {
        $this->table = $table;
    }

    public function handle(CashbookNumberPrefixQuery $query): ?string
    {
        return $this->table->getPrefix($query->getCashbookId()->toInt());
    }

}
