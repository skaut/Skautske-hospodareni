<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\QueryHandlers;

use Model\Google\ReadModel\Queries\UnitOAuthListQuery;
use Model\Mail\Repositories\IGoogleRepository;

final class UnitOAuthListQueryHandler
{
    /** @var IGoogleRepository */
    private $repository;

    public function __construct(IGoogleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UnitOAuthListQuery $query) : array
    {
        $list = $this->repository->getAll ($query->getUnitId ());


        return new Cashbook(
            $cashbook->getId(),
            $cashbook->getType(),
            $cashbook->getCashChitNumberPrefix(),
            $cashbook->getBankChitNumberPrefix(),
            $cashbook->getNote(),
            $cashbook->hasOnlyNumericChitNumbers()
        );
    }
}
