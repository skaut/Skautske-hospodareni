<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\Commands\Cashbook\LockCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

class LockCashbookHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    /**
     * @throws CashbookNotFoundException
     */
    public function handle(LockCashbook $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->lock($command->getUserId());

        $this->cashbooks->save($cashbook);
    }
}
