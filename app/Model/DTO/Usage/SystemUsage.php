<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

use DateTimeImmutable;

/**
 * Sessions in the system for one period, for the whole system or for one kraj:
 * the sections with their screens and the rows of the kraje, or of the units of the kraj.
 */
final class SystemUsage
{
    /**
     * @param list<int>          $months   months of the shown period
     * @param list<SectionUsage> $sections most used first
     * @param list<UnitUsage>    $rows     kraje, or units of the kraj, most used first
     */
    public function __construct(
        public readonly array $months,
        public readonly MonthlySessions $sessions,
        public readonly array $sections,
        public readonly array $rows,
        public readonly int $unitCount,
        public readonly ?DateTimeImmutable $countedSince,
    ) {
    }

    public function hasData(): bool
    {
        return $this->sessions->getTotal() > 0;
    }
}
