<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

use App\Model\Usage\UsageSection;

/**
 * One row of the kraje, of the okresy of a kraj or of the střediska of an okres: sessions in all sections and
 * in each section. A row with a kraj or an okres to open links to it.
 */
final class UnitUsage
{
    /**
     * @param array<string, MonthlySessions> $sections     section value => sessions
     * @param string|null                    $linkRegion   kraj the row opens
     * @param string|null                    $linkDistrict okres the row opens
     */
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly MonthlySessions $sessions,
        public readonly array $sections,
        public readonly ?string $linkRegion = null,
        public readonly ?string $linkDistrict = null,
    ) {
    }

    public function hasUnits(): bool
    {
        return $this->linkRegion !== null || $this->linkDistrict !== null;
    }

    /** Sessions of the row, of one section or, without it, of all sections. */
    public function get(?UsageSection $section): MonthlySessions
    {
        if ($section === null) {
            return $this->sessions;
        }

        return $this->sections[$section->value] ?? new MonthlySessions();
    }
}
