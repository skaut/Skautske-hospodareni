<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/** The general part of the administration's usage page: logins and preferences of the whole system. */
final class UsageOverview
{
    public function __construct(
        public readonly LoginStatistics $logins,
        public readonly PreferenceAdoption $preferences,
    ) {
    }
}
