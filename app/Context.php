<?php

declare(strict_types=1);

namespace App;

final class Context
{
    private EnvironmentMode $environmentMode;

    public function __construct(
        private string $appDir,
        private string $wwwDir,
        private bool $productionMode,
        string $environmentMode,
    ) {
        $this->environmentMode = EnvironmentMode::fromAppEnv($environmentMode);
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
        return $this->environmentMode->shouldShowBadge();
    }

    public function getEnvironmentLabel(): string
    {
        return $this->environmentMode->getLabel();
    }

    public function getEnvironmentMode(): string
    {
        return $this->environmentMode->value;
    }
}
