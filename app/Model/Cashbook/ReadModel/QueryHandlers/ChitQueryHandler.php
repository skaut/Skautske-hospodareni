<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitFactory;

final class ChitQueryHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private QueryBus $queryBus)
    {
    }

    public function __invoke(ChitQuery $query): ?Chit
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
