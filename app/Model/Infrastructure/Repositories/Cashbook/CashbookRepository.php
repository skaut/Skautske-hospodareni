<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Cashbook;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Infrastructure\Repositories\AggregateRepository;

use function sprintf;

final class CashbookRepository extends AggregateRepository implements ICashbookRepository
{
    public function find(CashbookId $id): Cashbook
    {
        $cashbook = $this->getEntityManager()->find(Cashbook::class, $id);

        if ($cashbook === null) {
            throw new CashbookNotFound(sprintf('Cashbook #%s not found', $id->toString()));
        }

        return $cashbook;
    }

    public function save(Cashbook $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
