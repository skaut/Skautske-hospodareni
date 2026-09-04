<?php

declare(strict_types=1);

namespace App\Model\DTO\Stat;

/**
 * Usage read out of data the application already stored for other reasons.
 *
 * These needed no new tracking, which is also their limitation — each one is a
 * by-product of some feature, so the caveats travel with them and are spelled
 * out on the individual getters.
 */
final class EngagementSignals
{
    /** @param array<string, int> $reportDeviceTypes device type => reports */
    public function __construct(
        public readonly int $pairingsTotal = 0,
        public readonly int $pairingsAutomatic = 0,
        public readonly int $paymentVisitUsers = 0,
        public readonly int $activeAuthors = 0,
        public readonly array $reportDeviceTypes = [],
    ) {
    }

    /** Share of bank transaction pairings the application made without a human. */
    public function getAutomaticPairingShare(): ?float
    {
        if ($this->pairingsTotal === 0) {
            return null;
        }

        return $this->pairingsAutomatic / $this->pairingsTotal * 100;
    }

    public function getReportDeviceTotal(): int
    {
        $total = 0;
        foreach ($this->reportDeviceTypes as $count) {
            $total += $count;
        }

        return $total;
    }
}
