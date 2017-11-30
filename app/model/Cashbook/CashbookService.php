<?php

namespace Model\Cashbook;

use Model\Skautis\Mapper;

class CashbookService
{

    /** @var Mapper */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getSkautisIdFromCashbookId(int $skautisId, ObjectType $type): int
    {
        return $this->mapper->getSkautisId($skautisId, $type->getValue());
    }

}
