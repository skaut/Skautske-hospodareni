<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\MoveChitsToDifferentCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class MoveChitsToDifferentCashbookHandler
{
    private ICashbookRepository $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function __invoke(MoveChitsToDifferentCashbook $command) : void
    {
        $sourceCashbook = $this->cashbooks->find($command->getSourceCashbookId());
        $targetCashbook = $this->cashbooks->find($command->getTargetCashbookId());

        $targetCashbook->copyChitsFrom($command->getChitIds(), $sourceCashbook);

        foreach ($command->getChitIds() as $chitId) {
            $sourceCashbook->removeChit($chitId);
        }

        $this->cashbooks->save($targetCashbook);
        $this->cashbooks->save($sourceCashbook);
    }
}
