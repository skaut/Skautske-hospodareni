<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateChitNumberPrefixHandler
{
    public function __construct(private ICashbookRepository $cashbooks)
    {
    }

    public function __invoke(UpdateChitNumberPrefix $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->updateChitNumberPrefix($command->getPrefix(), $command->getPaymentMethod());

        $this->cashbooks->save($cashbook);
    }
}
