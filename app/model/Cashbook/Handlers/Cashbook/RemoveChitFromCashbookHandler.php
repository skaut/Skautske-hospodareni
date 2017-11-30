<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\RemoveChitFromCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class RemoveChitFromCashbookHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(RemoveChitFromCashbook $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->removeChit($command->getChitId());

        $this->cashbooks->save($cashbook);
    }

}
