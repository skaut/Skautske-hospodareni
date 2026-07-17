<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Google\OAuthId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use LogicException;

use function is_string;

final class OAuthIdType extends GuidType
{
    public function getName(): string
    {
        return 'oauth_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OAuthId
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException('Assertion failed.');
        }

        return OAuthId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof OAuthId) {
            throw new LogicException('Assertion failed.');
        }

        return $value->toString();
    }
}
