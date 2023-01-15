<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UnlockChit;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UnlockChitHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(UnlockChit $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->unlockChit($command->getChitId());

        $this->cashbooks->save($cashbook);
    }
}
