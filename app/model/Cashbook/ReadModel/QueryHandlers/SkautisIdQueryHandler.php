<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Skautis\Mapper;

class SkautisIdQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var Mapper */
    private $mapper;

    public function __construct(ICashbookRepository $cashbooks, Mapper $mapper)
    {
        $this->cashbooks = $cashbooks;
        $this->mapper = $mapper;
    }

    public function handle(SkautisIdQuery $query): int
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());
        $objectType = $cashbook->getType()->getSkautisObjectType()->getValue();

        return $this->mapper->getSkautisId($cashbook->getId(), $objectType);
    }

}
