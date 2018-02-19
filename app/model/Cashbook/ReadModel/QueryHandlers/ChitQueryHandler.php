<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitFactory;

final class ChitQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(ChitQuery $query): ?Chit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        foreach ($cashbook->getChits() as $chit) {
            if ($chit->getId() === $query->getChitId()) {
                return ChitFactory::create($chit);
            }
        }

        return NULL;
    }

}
