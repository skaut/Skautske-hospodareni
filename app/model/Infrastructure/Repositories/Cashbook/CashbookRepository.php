<?php

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Infrastructure\Repositories\AbstractRepository;

class CashbookRepository extends AbstractRepository implements ICashbookRepository
{

    public function find(int $id): Cashbook
    {
        $cashboook = $this->getEntityManager()->find(Cashbook::class, $id);

        if($cashboook === NULL) {
            throw new CashbookNotFoundException();
        }

        return $cashboook;
    }

    public function save(Cashbook $cashbook): void
    {
        $this->saveAndDispatchEvents($cashbook);
    }

}
