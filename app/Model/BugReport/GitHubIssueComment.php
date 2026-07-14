<?php

declare(strict_types=1);

namespace App\Model\BugReport;

final class GitHubIssueComment
{
    public function __construct(private string $url)
    {
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
