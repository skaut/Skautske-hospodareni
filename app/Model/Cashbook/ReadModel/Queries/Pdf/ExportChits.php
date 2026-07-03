<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries\Pdf;

use App\Model\Cashbook\Cashbook\CashbookId;

/** @see ExportChitsHandler */
final class ExportChits
{
    /**
     * Use static factory method.
     *
     * @param int[]|null $chitIds
     */
    private function __construct(private CashbookId $cashbookId, private ?array $chitIds = null)
    {
    }

    /** @param int[]|null $chitIds */
    public static function withChitIds(CashbookId $cashbookId, ?array $chitIds): self
    {
        return new self($cashbookId, $chitIds);
    }

    public static function all(CashbookId $cashbookId): self
    {
        return new self($cashbookId, null);
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    /** @return int[]|null */
    public function getChitIds(): ?array
    {
        return $this->chitIds;
    }
}
