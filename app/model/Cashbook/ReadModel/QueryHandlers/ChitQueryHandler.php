<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitFactory;

final class ChitQueryHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private QueryBus $queryBus)
    {
    }

    public function __invoke(ChitQuery $query): Chit|null
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        foreach ($cashbook->getChits() as $chit) {
            if ($chit->getId() === $query->getChitId()) {
                $categories = $this->queryBus->handle(new CategoryListQuery($query->getCashbookId()));

                return ChitFactory::create($chit, $categories);
            }
        }

        return null;
    }
}
