<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/**
 * How many people changed the optional behaviours away from their defaults.
 *
 * System-wide: a preference row has no unit, so these figures ignore the unit
 * filter. `$users` counts people who ever saved any preference at all — anyone
 * who never opened the settings has no row and is not represented here.
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

    public function getShowHelpShare(): ?float
    {
        return $this->share($this->showHelp);
    }

    public function getExtendLoginShare(): ?float
    {
        return $this->share($this->extendLogin);
    }

    public function getRememberRoleShare(): ?float
    {
        return $this->share($this->rememberRole);
    }

    private function share(int $count): ?float
    {
        if ($this->users === 0) {
            return null;
        }

        return $count / $this->users * 100;
    }
}
