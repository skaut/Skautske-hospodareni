<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\MoveChitsToDifferentCashbookHandler;

/**
 * @see MoveChitsToDifferentCashbookHandler
 */
final class MoveChitsToDifferentCashbook
{

    /** @var int[] */
    private $chitIds;

    /** @var int */
    private $sourceCashbookId;

    /** @var int */
    private $targetCashbookId;

    /**
     * @param int[] $chitIds
     */
    public function __construct(array $chitIds, int $sourceCashbookId, int $targetCashbookId)
    {
        $this->chitIds = $chitIds;
        $this->sourceCashbookId = $sourceCashbookId;
        $this->targetCashbookId = $targetCashbookId;
    }

    /**
     * @return int[]
     */
    public function getChitIds(): array
    {
        return $this->chitIds;
    }

    public function getSourceCashbookId(): int
    {
        return $this->sourceCashbookId;
    }

    public function getTargetCashbookId(): int
    {
        return $this->targetCashbookId;
    }

}
