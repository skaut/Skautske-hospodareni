<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\CashbookNotFound;
use App\Model\Cashbook\Commands\Cashbook\LockCashbook;
use App\Model\Cashbook\Repositories\ICashbookRepository;

class LockCashbookHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    /** @throws CashbookNotFound */
    public function __invoke(LockCashbook $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->lock($command->getUserId());

        $this->cashbooks->save($cashbook);
    }
}
