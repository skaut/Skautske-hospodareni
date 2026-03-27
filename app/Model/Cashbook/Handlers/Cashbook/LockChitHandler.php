<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\LockChit;
use App\Model\Cashbook\Repositories\ICashbookRepository;

class LockChitHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(LockChit $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->lockChit($command->getChitId(), $command->getUserId());

        $this->cashbooks->save($cashbook);
    }
}
