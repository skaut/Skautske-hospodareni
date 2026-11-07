<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

use App\Model\Usage\UsageSection;

/** Sessions in one section of the system together with its screens, most used first. */
final class SectionUsage
{
    /** @param list<ScreenUsage> $screens */
    public function __construct(
        public readonly UsageSection $section,
        public readonly MonthlySessions $sessions,
        public readonly array $screens,
    ) {
    }

    /** Share of the sessions in the whole shown scope that used this section, in percent. */
    public function getShare(int $totalSessions): ?float
    {
        if ($totalSessions === 0) {
            return null;
        }

        return $this->sessions->getTotal() / $totalSessions * 100;
    }
}
