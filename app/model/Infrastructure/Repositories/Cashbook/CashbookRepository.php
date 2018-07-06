<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Infrastructure\Repositories\AbstractRepository;

class CashbookRepository extends AbstractRepository implements ICashbookRepository
{
    public function find(CashbookId $id) : Cashbook
    {
        $cashboook = $this->getEntityManager()->find(Cashbook::class, $id);

        if ($cashboook === null) {
            throw new CashbookNotFoundException("Cashbook #$id not found");
        }

        return $cashboook;
    }

    public function save(Cashbook $cashbook) : void
    {
        $this->saveAndDispatchEvents($cashbook);
    }
}
