<?php

declare(strict_types=1);

namespace App\Model\DTO\Usage;

use DateTimeImmutable;

/** Sessions per page over one range of days, together with how far back counting reaches. */
final class PageSessions
{
    /** @param array<string, int> $sessions page key => sessions, most used first */
    public function __construct(
        public readonly array $sessions = [],
        public readonly ?DateTimeImmutable $countedSince = null,
    ) {
    }

    public function get(string $pageKey): int
    {
        return $this->sessions[$pageKey] ?? 0;
    }
}
