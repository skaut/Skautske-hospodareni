<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

use function array_sum;
use function array_values;
use function max;

/**
 * Everything derived from the `user_login` table.
 *
 * Session length is measured from the first to the last real request, so it
 * describes how long someone worked — not how long their skautIS token stayed
 * valid. Keep-alive pings are excluded at the point of writing.
 */
final class LoginStatistics
{
    /**
     * @param array<string, int>          $deviceTypes device type => logins
     * @param array<string, int>          $browsers    browser => logins
     * @param array<string, int>          $platforms   platform => logins
     * @param array<string, int>          $roleGroups  role group label => logins
     * @param array<int, array<int, int>> $weekMap     day (1 = Monday) => hour (0-23) => logins
     * @param array<int, int>             $monthly     month (1-12) => logins
     */
    public function __construct(
        public readonly bool $available = false,
        public readonly int $logins = 0,
        public readonly int $users = 0,
        public readonly int $units = 0,
        public readonly int $newUsers = 0,
        public readonly int $loginsAllUnits = 0,
        public readonly int $usersAllUnits = 0,
        public readonly ?int $sessionMedianSeconds = null,
        public readonly ?int $sessionP90Seconds = null,
        public readonly int $endedByLogout = 0,
        public readonly array $deviceTypes = [],
        public readonly array $browsers = [],
        public readonly array $platforms = [],
        public readonly array $roleGroups = [],
        public readonly array $weekMap = [],
        public readonly array $monthly = [],
    ) {
    }

    public static function unavailable(): self
    {
        return new self();
    }

    public function hasData(): bool
    {
        return $this->available && $this->logins > 0;
    }

    public function getReturningUsers(): int
    {
        return max(0, $this->users - $this->newUsers);
    }

    public function getSessionsPerUser(): ?float
    {
        if ($this->users === 0) {
            return null;
        }

        return $this->logins / $this->users;
    }

    /**
     * Sessions that simply stopped — the person closed the tab or the skautIS
     * token ran out. There is no way to tell those two apart from the outside,
     * so they are reported together rather than guessed at.
     */
    public function getEndedWithoutLogout(): int
    {
        return max(0, $this->logins - $this->endedByLogout);
    }

    public function getLogoutShare(): ?float
    {
        if ($this->logins === 0) {
            return null;
        }

        return $this->endedByLogout / $this->logins * 100;
    }

    public function getWeekPeak(): int
    {
        $peak = 0;
        foreach ($this->weekMap as $hours) {
            $peak = max($peak, ...[0, ...$hours]);
        }

        return $peak;
    }

    public function getMonthlyPeak(): int
    {
        return max(0, ...array_values($this->monthly));
    }

    public function getWeekTotal(): int
    {
        $total = 0;
        foreach ($this->weekMap as $hours) {
            $total += array_sum($hours);
        }

        return $total;
    }
}
