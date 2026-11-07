<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/**
 * How many people switched on the options of the user settings.
 *
 * System-wide: a preference row has no unit. `$users` counts people who ever saved any preference at all —
 * anyone who never opened the settings has no row and is not represented here.
 */
final class PreferenceAdoption
{
    public function __construct(
        public readonly int $users = 0,
        public readonly int $showHelp = 0,
        public readonly int $extendLogin = 0,
        public readonly int $rememberRole = 0,
    ) {
    }
}
