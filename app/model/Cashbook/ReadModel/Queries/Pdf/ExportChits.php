<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries\Pdf;

use Model\Cashbook\Cashbook\CashbookId;

/** @see ExportChitsHandler */
final class ExportChits
{
    /**
     * Use static factory method
     *
     * @param int[]|NULL $chitIds
     */
    private function __construct(private CashbookId $cashbookId, private array|null $chitIds = null)
    {
    }

    /** @param int[]|null $chitIds */
    public static function withChitIds(CashbookId $cashbookId, array|null $chitIds): self
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
    public function getChitIds(): array|null
    {
        return $this->chitIds;
    }
}
