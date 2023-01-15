<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisCampId;

use function array_map;

/** @see CampStatisticsQueryHandler */
final class CampStatisticsQuery
{
    /** @var SkautisCampId[] */
    private array $campIds;

    /** @param int[] $campIds */
    public function __construct(array $campIds, private int $year)
    {
        $this->campIds = array_map(function (int $id) {
            return new SkautisCampId($id);
        }, $campIds);
    }

    /** @return SkautisCampId[] */
    public function getCampIds(): array
    {
        return $this->campIds;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
