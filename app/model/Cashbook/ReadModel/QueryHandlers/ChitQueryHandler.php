<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitFactory;

final class ChitQueryHandler
{
    private ICashbookRepository $cashbooks;

    private QueryBus $queryBus;

    public function __construct(ICashbookRepository $cashbooks, QueryBus $queryBus)
    {
        $this->cashbooks = $cashbooks;
        $this->queryBus  = $queryBus;
    }

    public function __invoke(ChitQuery $query) : ?Chit
    {
        $cashbook   = $this->cashbooks->find($query->getCashbookId());
        $categories = $this->queryBus->handle(new CategoryListQuery($query->getCashbookId()));

        foreach ($cashbook->getChits() as $chit) {
            if ($chit->getId() === $query->getChitId()) {
                return ChitFactory::create($chit, $categories);
            }
        }

        return null;
    }
}
