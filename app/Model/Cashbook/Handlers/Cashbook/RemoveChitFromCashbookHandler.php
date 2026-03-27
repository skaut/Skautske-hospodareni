<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\RemoveChitFromCashbook;
use App\Model\Cashbook\Repositories\ICashbookRepository;

final class RemoveChitFromCashbookHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(RemoveChitFromCashbook $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->removeChit($command->getChitId());

        $this->cashbooks->save($cashbook);
    }
}
