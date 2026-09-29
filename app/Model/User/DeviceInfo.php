<?php

declare(strict_types=1);

namespace App\Model\User;

/**
 * Coarse description of the device a login came from.
 *
 * Deliberately shallow: family and major version are enough to decide what the
 * frontend has to support, and anything finer would be a needlessly precise
 * personal detail to keep around.
 */
final class DeviceInfo
{
    public const TYPE_DESKTOP = 'desktop';
    public const TYPE_MOBILE = 'mobile';
    public const TYPE_TABLET = 'tablet';
    public const TYPE_BOT = 'bot';
    public const TYPE_UNKNOWN = 'unknown';

    public const UNKNOWN_LABEL = 'Neznámý';

    public function __construct(
        private readonly string $type,
        private readonly string $browser,
        private readonly ?string $browserVersion,
        private readonly string $platform,
    ) {
    }

    public static function unknown(): self
    {
        return new self(self::TYPE_UNKNOWN, self::UNKNOWN_LABEL, null, self::UNKNOWN_LABEL);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }
}
