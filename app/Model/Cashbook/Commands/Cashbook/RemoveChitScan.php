<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\RemoveChitScanHandler;
use App\Model\Common\FilePath;

/** @see RemoveChitScanHandler */
final class RemoveChitScan
{
    public function __construct(private CashbookId $cashbookId, private int $chitId, private FilePath $path)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

    public function getPath(): FilePath
    {
        return $this->path;
    }
}
