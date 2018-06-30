<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\Repositories\ICashbookRepository;

final class UpdateChitNumberPrefixHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    public function __construct(ICashbookRepository $cashbooks)
    {
        $this->cashbooks = $cashbooks;
    }

    public function handle(UpdateChitNumberPrefix $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());

        $cashbook->updateChitNumberPrefix($command->getPrefix());

        $this->cashbooks->save($cashbook);
    }

}
