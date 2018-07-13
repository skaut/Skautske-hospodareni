<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\ClearCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class ClearCashbookHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(ClearCashbook $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->clear();

        $this->cashbooks->save($cashbook);
    }
}
