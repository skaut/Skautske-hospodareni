<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Model\Travel\Travel\TransportType;
use Nette\Utils\Json;
use function array_map;

final class TransportTypeListType extends Type
{
    public function getName() : string
    {
        return 'transport_types';
    }

    /**
     * @param array<string, mixed> $fieldDeclaration
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) : string
    {
        return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : string
    {
        Assertion::isArray($value);
        Assertion::allIsInstanceOf($value, TransportType::class);

        return Json::encode(array_map(fn(TransportType $type) => $type->toString(), $value));
    }

    /**
     * @param mixed $value
     *
     * @return TransportType[]
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : array
    {
        $types = Json::decode($value);

        Assertion::allString($types);

        return array_map(fn(string $type) => TransportType::get($type), $types);
    }
}
