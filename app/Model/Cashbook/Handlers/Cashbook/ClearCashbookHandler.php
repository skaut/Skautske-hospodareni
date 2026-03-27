<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\ClearCashbook;
use App\Model\Cashbook\Repositories\ICashbookRepository;

final class ClearCashbookHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(ClearCashbook $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->clear();

        $this->cashbooks->save($cashbook);
    }
}
