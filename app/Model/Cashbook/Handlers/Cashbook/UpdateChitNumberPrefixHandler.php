<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use App\Model\Cashbook\Repositories\ICashbookRepository;

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
