<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Google\OAuthId;

use function assert;
use function is_string;

final class OAuthIdType extends GuidType
{
    public function getName(): string
    {
        return 'oauth_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): OAuthId|null
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return OAuthId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof OAuthId);

        return $value->toString();
    }
}
