<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Commands\Cashbook\CreateCashbook;
use App\Model\Cashbook\Repositories\ICashbookRepository;

final class CreateCashbookHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(CreateCashbook $command): void
    {
        $cashbook = new Cashbook($command->getId(), $command->getType());

        $this->cashbooks->save($cashbook);
    }
}
