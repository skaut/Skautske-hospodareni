<?php

declare(strict_types=1);

namespace App\Model\BugReport;

final readonly class BugReportScreenshot
{
    public function __construct(
        private string $path,
        private string $originalName,
        private string $contentType,
        private int $size,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
