<?php

declare(strict_types=1);

namespace App\Model\BugReport;

final class GitHubIssue
{
    public function __construct(
        private int $number,
        private string $url,
    ) {
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
