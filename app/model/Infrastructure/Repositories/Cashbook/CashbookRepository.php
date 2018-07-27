<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Infrastructure\Repositories\AggregateRepository;
use function sprintf;

class CashbookRepository extends AggregateRepository implements ICashbookRepository
{
    public function find(CashbookId $id) : Cashbook
    {
        $cashboook = $this->getEntityManager()->find(Cashbook::class, $id);

        if ($cashboook === null) {
            throw new CashbookNotFound(sprintf('Cashbook #%s not found', $id->toString()));
        }

        return $cashboook;
    }

    public function save(Cashbook $cashbook) : void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
