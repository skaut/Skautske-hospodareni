<?php

declare(strict_types=1);

namespace App;

final class Context
{
    public function __construct(private string $appDir, private string $wwwDir, private bool $productionMode, private bool $showTestBackground)
    {
    }

    public function getAppDir(): string
    {
        return $this->appDir;
    }

    public function getWwwDir(): string
    {
        return $this->wwwDir;
    }

    public function isProduction(): bool
    {
        return $this->productionMode;
    }

    public function shouldShowTestBackground(): bool
    {
        return $this->showTestBackground;
    }
}
