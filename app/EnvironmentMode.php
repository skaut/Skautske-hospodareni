<?php

declare(strict_types=1);

namespace App;

enum EnvironmentMode: string
{
    case DEVELOPMENT = 'dev';
    case TEST = 'test';
    case BETA = 'beta';
    case PRODUCTION = 'prod';

    public static function fromAppEnv(string $appEnv): self
    {
        return match ($appEnv) {
            self::DEVELOPMENT->value => self::DEVELOPMENT,
            self::BETA->value => self::BETA,
            self::PRODUCTION->value => self::PRODUCTION,
            default => self::TEST,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DEVELOPMENT => 'Lokální vývoj',
            self::TEST => 'Testovací server',
            self::BETA => 'Beta server',
            self::PRODUCTION => 'Produkce',
        };
    }

    public function shouldShowBadge(): bool
    {
        return match ($this) {
            self::PRODUCTION => false,
            default => true,
        };
    }
}
