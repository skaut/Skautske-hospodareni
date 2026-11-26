<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/** How many open payment groups use the automations. Closed groups are left out, as in Admin:Cron. */
final class AutomationAdoption
{
    public function __construct(
        public readonly int $openGroups = 0,
        public readonly int $automaticPairing = 0,
        public readonly int $automaticReminders = 0,
    ) {
    }

    public function getAutomaticPairingShare(): ?float
    {
        return $this->share($this->automaticPairing);
    }

    public function getAutomaticRemindersShare(): ?float
    {
        return $this->share($this->automaticReminders);
    }

    private function share(int $count): ?float
    {
        if ($this->openGroups === 0) {
            return null;
        }

        return $count / $this->openGroups * 100;
    }
}
