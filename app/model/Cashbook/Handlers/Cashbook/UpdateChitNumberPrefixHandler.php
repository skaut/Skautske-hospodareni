<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateChitNumberPrefixHandler
{
    private ICashbookRepository $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function __invoke(UpdateChitNumberPrefix $command) : void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->updateChitNumberPrefix($command->getPrefix(), $command->getPaymentMethod());

        $this->cashbooks->save($cashbook);
    }
}
