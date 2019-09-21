<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\FilePath;

final class RemoveChitScan
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var FilePath */
    private $path;

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
