<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\LockChit;
use Model\Cashbook\Repositories\ICashbookRepository;

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
