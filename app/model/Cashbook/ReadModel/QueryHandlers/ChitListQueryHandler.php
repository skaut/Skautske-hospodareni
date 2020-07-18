<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Chit as ChitDTO;
use Model\DTO\Cashbook\ChitFactory;
use function array_map;
use function array_values;
use function assert;

class ChitListQueryHandler
{
    private EntityManager $entityManager;

    private QueryBus $queryBus;

    public function __construct(EntityManager $entityManager, QueryBus $queryBus)
    {
        $this->entityManager = $entityManager;
        $this->queryBus      = $queryBus;
    }

    /**
     * @return ChitDTO[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(ChitListQuery $query) : array
    {
        $cashbook = $this->entityManager->find(Cashbook::class, $query->getCashbookId());

        if ($cashbook === null) {
            return [];
        }

        assert($cashbook instanceof Cashbook);

        $categories = $this->queryBus->handle(new CategoryListQuery($query->getCashbookId()));

        return array_map(
            function (Chit $chit) use ($categories) : ChitDTO {
                return ChitFactory::create($chit, $categories);
            },
            array_values($cashbook->getChits($query->getPaymentMethod())),
        );
    }
}
