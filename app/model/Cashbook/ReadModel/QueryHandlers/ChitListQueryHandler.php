<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Chit as ChitDTO;
use Model\Utils\Arrays;
use function array_map;

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
        $chits = Arrays::sort(
            $chits,
            function (Chit $a, Chit $b): int {
                return $a->getDate() <=> $b->getDate();
                },
            function (Chit $a, Chit $b): int {
                return $a->getCategory()->getOperationType()->compareWith($b->getCategory()->getOperationType());
                },
            function (Chit $a, Chit $b): int {
                return $a->getId() <=> $b->getId();
            });

        return array_map(function (Chit $chit): ChitDTO {
            return new ChitDTO(
                $chit->getId(),
                $chit->getNumber(),
                $chit->getDate(),
                $chit->getRecipient(),
                $chit->getAmount(),
                $chit->getPurpose(),
                $chit->getCategory(),
                $chit->isLocked()
            );
        }, $chits);
    }

}
