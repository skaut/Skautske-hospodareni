<?php

declare(strict_types=1);

namespace App;

final class Context
{
    public function __construct(
        private string $appDir,
        private bool $productionMode,
        private bool $showTestBackground,
        private string $environmentLabel,
        private string $environmentColor,
    ) {
    }

    public function getAppDir(): string
    {
        return $this->appDir;
    }

    public function isProduction(): bool
    {
        return $this->productionMode;
    }

    public function shouldShowTestBackground(): bool
    {
        return $this->showTestBackground;
    }

    public function getEnvironmentLabel(): string
    {
        return $this->environmentLabel;
    }

    public function getEnvironmentColor(): string
    {
        return $this->environmentColor;
    }
}
