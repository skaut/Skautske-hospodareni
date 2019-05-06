<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UnlockChit;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UnlockChitHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function __invoke(UnlockChit $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->unlockChit($command->getChitId());

        $this->cashbooks->save($cashbook);
    }
}
