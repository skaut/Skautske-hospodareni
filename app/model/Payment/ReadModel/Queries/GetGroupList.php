<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\GetGroupListHandler;

/**
 * @see GetGroupListHandler
 */
final class GetGroupList
{
    /** @var int[] */
    private $unitIds;

    private bool $onlyOpen;

    /**
     * @param int[] $unitIds
     */
    public function __construct(array $unitIds, bool $onlyOpen)
    {
        $this->unitIds  = $unitIds;
        $this->onlyOpen = $onlyOpen;
    }

    /**
     * @return int[]
     */
    public function getUnitIds() : array
    {
        return $this->unitIds;
    }

    public function onlyOpen() : bool
    {
        return $this->onlyOpen;
    }
}
