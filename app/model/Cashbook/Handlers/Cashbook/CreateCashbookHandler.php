<?php

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Repositories\ICashbookRepository;

final class CreateCashbookHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(CreateCashbook $command): void
    {
        $cashbook = new Cashbook($command->getId());

        $this->cashbooks->save($cashbook);
    }


}
