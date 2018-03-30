<?php

namespace Model\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Skautis\Mapper;

class CashbookService
{

    /** @var Mapper */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getSkautisIdFromCashbookId(CashbookId $cashbookId, ObjectType $type): int
    {
        return $this->mapper->getSkautisId($cashbookId, $type->getValue());
    }

}
