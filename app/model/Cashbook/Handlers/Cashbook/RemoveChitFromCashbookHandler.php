<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\RemoveChitFromCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class RemoveChitFromCashbookHandler
{
    private ICashbookRepository $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function __invoke(RemoveChitFromCashbook $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->removeChit($command->getChitId());

        $this->cashbooks->save($cashbook);
    }
}
