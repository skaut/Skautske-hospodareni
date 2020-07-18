<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\RemoveChitScanHandler;
use Model\Common\FilePath;

/**
 * @see RemoveChitScanHandler
 */
final class RemoveChitScan
{
    private CashbookId $cashbookId;

    private int $chitId;

    private FilePath $path;

    public function __construct(CashbookId $cashbookId, int $chitId, FilePath $path)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId     = $chitId;
        $this->path       = $path;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }

    public function getPath() : FilePath
    {
        return $this->path;
    }
}
