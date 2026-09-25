<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/** Everything the administration's usage page shows, for one unit tree and one year. */
final class UsageOverview
{
    public function __construct(
        public readonly LoginStatistics $logins,
        public readonly PreferenceAdoption $preferences,
        public readonly EngagementSignals $engagement,
    ) {
    }
}
