<?php

declare(strict_types=1);

namespace App\Components\Payment;

final class EmptyPairButtonScope implements PairButtonScope
{
    public function getItemsCount(): int
    {
        return 0;
    }

    public function canPair(): bool
    {
        return false;
    }

    public function getDaysBackDefault(): int
    {
        return 60;
    }

    public function getDisabledReason(): string
    {
        return 'Není vybraný žádný rozsah pro párování.';
    }

    public function pair(?int $daysBack = null): array
    {
        return [];
    }
}
