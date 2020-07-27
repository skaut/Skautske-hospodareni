<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Google\OAuthId;

final class OAuthIdType extends GuidType
{
    public function getName() : string
    {
        return 'ouath_id';
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?OAuthId
    {
        if ($value === null) {
            return null;
        }

        /** @var string $value */
        return OAuthId::fromString($value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?string
    {
        if ($value === null) {
            return null;
        }

        /** @var OAuthId $value */
        return $value->toString();
    }
}
