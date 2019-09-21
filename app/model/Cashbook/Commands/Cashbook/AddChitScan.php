<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\AddChitScanHandler;

/**
 * @see AddChitScanHandler
 */
final class AddChitScan
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var string */
    private $fileName;

    /** @var string */
    private $scanContents;

    public function __construct(CashbookId $cashbookId, int $chitId, string $fileName, string $scanContents)
    {
        $this->cashbookId   = $cashbookId;
        $this->chitId       = $chitId;
        $this->fileName     = $fileName;
        $this->scanContents = $scanContents;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }

    public function getFileName() : string
    {
        return $this->fileName;
    }

    public function getScanContents() : string
    {
        return $this->scanContents;
    }
}
