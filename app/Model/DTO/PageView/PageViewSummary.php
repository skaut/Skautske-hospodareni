<?php

declare(strict_types=1);

namespace App\Model\DTO\PageView;

use DateTimeImmutable;

/** Views per page over one range of days, together with how far back counting reaches. */
final class PageViewSummary
{
    /** @param array<string, int> $views page key => views, most used first */
    public function __construct(
        public readonly array $views = [],
        public readonly ?DateTimeImmutable $countedSince = null,
    ) {
    }

    public function getViews(string $pageKey): int
    {
        return $this->views[$pageKey] ?? 0;
    }
}
