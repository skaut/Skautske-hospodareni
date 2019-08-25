<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Cashbook;

use Model\Cashbook\Cashbook\ChitScan;
use Model\Cashbook\Commands\Cashbook\AddChitScan;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Common\FilePath;
use Model\Common\IScanStorage;

final class AddChitScanHandler
{
    /** @var ICashbookRepository */
    private $cashbook;

    /** @var IScanStorage */
    private $scanStorage;

    public function __construct(
        ICashbookRepository $cashbook,
        IScanStorage $scanStorage
    ) {
        $this->cashbook    = $cashbook;
        $this->scanStorage = $scanStorage;
    }

    public function __invoke(AddChitScan $command) : void
    {
        $path = FilePath::generate(ChitScan::FILE_PATH_PREFIX, $command->getFileName());
        $this->scanStorage->save($path, $command->getScanContents());

        $cashbook = $this->cashbook->find($command->getCashbookId());
        $cashbook->addChitScan($command->getChitId(), $path);
        $this->cashbook->save($cashbook);
    }
}
