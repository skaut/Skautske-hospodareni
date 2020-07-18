<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class CreateCashbookHandler
{
    private ICashbookRepository $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function __invoke(CreateCashbook $command) : void
    {
        $cashbook = new Cashbook($command->getId(), $command->getType());

        $this->cashbooks->save($cashbook);
    }
}
