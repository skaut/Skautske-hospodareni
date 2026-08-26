<?php

declare(strict_types=1);

namespace App;

final class Context
{
    public function __construct(
        private string $appDir,
        private string $wwwDir,
        private bool $productionMode,
        private string $environmentMode,
        private string $environmentLabel,
    ) {
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

    public function shouldShowEnvironmentBadge(): bool
    {
        return $this->environmentMode !== EnvironmentMode::PRODUCTION->value;
    }

    public function getEnvironmentLabel(): string
    {
        return $this->environmentLabel;
    }

    public function getEnvironmentMode(): string
    {
        return $this->environmentMode;
    }
}
