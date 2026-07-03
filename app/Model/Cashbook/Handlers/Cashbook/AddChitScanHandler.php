<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Cashbook\ChitScan;
use App\Model\Cashbook\Commands\Cashbook\AddChitScan;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use App\Model\Common\FilePath;
use App\Model\Common\IScanStorage;

final class AddChitScanHandler
{
    public function __construct(
        private ICashbookRepository $cashbook,
        private IScanStorage $scanStorage,
    ) {
    }

    public function __invoke(AddChitScan $command): void
    {
        $path = FilePath::generate(ChitScan::FILE_PATH_PREFIX, $command->getFileName());
        $this->scanStorage->save($path, $command->getScanContents());

        $cashbook = $this->cashbook->find($command->getCashbookId());
        $cashbook->addChitScan($command->getChitId(), $path);
        $this->cashbook->save($cashbook);
    }
}
