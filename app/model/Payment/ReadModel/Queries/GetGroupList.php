<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class GetGroupList
{
    /** @var int[] */
    private $unitIds;

    /** @var bool */
    private $onlyOpen;

    public function __construct(array $unitIds, bool $onlyOpen)
    {
        $this->unitIds = $unitIds;
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
