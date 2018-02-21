<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Chit as ChitDTO;
use Model\DTO\Cashbook\ChitFactory;
use function array_map;
use function usort;

class ChitListQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    /**
     * @return ChitDTO[]
     * @throws \Model\Cashbook\CashbookNotFoundException
     */
    public function handle(ChitListQuery $query): array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $chits = $cashbook->getChits();

        usort($chits, function (Chit $a, Chit $b): int {
            return [
                $a->getDate(),
                $a->getCategory()->getOperationType()->getValue(),
                $a->getId(),
            ] <=> [
                $b->getDate(),
                $b->getCategory()->getOperationType()->getValue(),
                $b->getId(),
            ];
        });

        return array_map([ChitFactory::class, 'create'], $chits);
    }

}
