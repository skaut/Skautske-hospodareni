<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\Chit;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Chit as ChitDTO;
use App\Model\DTO\Cashbook\ChitFactory;
use Doctrine\ORM\EntityManager;

use function array_map;
use function array_values;
use function assert;

class ChitListQueryHandler
{
    public function __construct(private EntityManager $entityManager, private QueryBus $queryBus)
    {
    }

    /**
     * @return ChitDTO[]
     *
     * @throws CashbookNotFound
     */
    public function __invoke(ChitListQuery $query): array
    {
        $cashbook = $this->entityManager->find(Cashbook::class, $query->getCashbookId());

        if ($cashbook === null) {
            return [];
        }

        assert($cashbook instanceof Cashbook);

        $categories = $this->queryBus->handle(new CategoryListQuery($query->getCashbookId()));

        return array_map(
            function (Chit $chit) use ($categories): ChitDTO {
                return ChitFactory::create($chit, $categories);
            },
            array_values($cashbook->getChits($query->getPaymentMethod())),
        );
    }
}
