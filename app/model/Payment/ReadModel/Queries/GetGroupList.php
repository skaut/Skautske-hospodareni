<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\GetGroupListHandler;

/** @see GetGroupListHandler */
final class GetGroupList
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds, private bool $onlyOpen)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }

    public function onlyOpen(): bool
    {
        return $this->onlyOpen;
    }
}
