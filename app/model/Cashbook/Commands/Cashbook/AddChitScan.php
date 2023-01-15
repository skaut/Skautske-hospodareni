<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\AddChitScanHandler;

/** @see AddChitScanHandler */
final class AddChitScan
{
    public function __construct(private CashbookId $cashbookId, private int $chitId, private string $fileName, private string $scanContents)
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

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getScanContents(): string
    {
        return $this->scanContents;
    }
}
