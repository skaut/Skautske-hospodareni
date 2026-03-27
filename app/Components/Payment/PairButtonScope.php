<?php

declare(strict_types=1);

namespace App\Components\Payment;

interface PairButtonScope
{
    public function getItemsCount(): int;

    public function canPair(): bool;

    public function getDaysBackDefault(): int;

    public function getDisabledReason(): string;

    /** @return list<PairButtonFlashMessage> */
    public function pair(?int $daysBack = null): array;
}
