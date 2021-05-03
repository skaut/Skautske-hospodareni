<?php

declare(strict_types=1);

namespace App;

final class Context
{
    private string $appDir;

    private string $wwwDir;

    private bool $productionMode;

    private bool $showTestBackground;

    public function __construct(string $appDir, string $wwwDir, bool $productionMode, bool $showTestBackground)
    {
        $this->appDir             = $appDir;
        $this->wwwDir             = $wwwDir;
        $this->productionMode     = $productionMode;
        $this->showTestBackground = $showTestBackground;
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
