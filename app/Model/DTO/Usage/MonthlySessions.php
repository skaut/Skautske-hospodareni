<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

use function array_sum;

/** Sessions of one row of the usage overview, per month of the shown period. */
final class MonthlySessions
{
    /** @param array<int, int> $byMonth month (1-12) => sessions; months without sessions may be missing */
    public function __construct(public readonly array $byMonth = [])
    {
    }

    public function getTotal(): int
    {
        return array_sum($this->byMonth);
    }

    public function getMonth(int $month): int
    {
        return $this->byMonth[$month] ?? 0;
    }

    public function with(int $month, int $sessions): self
    {
        $byMonth = $this->byMonth;
        $byMonth[$month] = ($byMonth[$month] ?? 0) + $sessions;

        return new self($byMonth);
    }
}
