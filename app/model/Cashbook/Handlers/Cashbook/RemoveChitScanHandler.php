<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Commands\Cashbook\RemoveChitScan;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Common\IScanStorage;

final class RemoveChitScanHandler
{
    public function __construct(private ICashbookRepository $cashbooks, private IScanStorage $storage)
    {
    }

    public function __invoke(RemoveChitScan $command): void
    {
        $cashbook = $this->cashbooks->find($command->getCashbookId());
        $cashbook->removeChitScan($command->getChitId(), $command->getPath());
        $this->cashbooks->save($cashbook);

        $this->storage->delete($command->getPath());
    }
}
