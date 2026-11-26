<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

/** Sessions on one screen of a section. */
final class ScreenUsage
{
    public function __construct(
        public readonly string $label,
        public readonly MonthlySessions $sessions,
    ) {
    }
}
