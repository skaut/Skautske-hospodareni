<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Assert\Assertion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Model\Travel\Travel\TransportType;
use Nette\Utils\Json;

use function array_map;
use function array_values;

final class TransportTypeListType extends Type
{
    public function getName(): string
    {
        return 'transport_types';
    }

    /** @param array<string, mixed> $fieldDeclaration */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        Assertion::isArray($value);
        Assertion::same($value, array_values($value));
        Assertion::allIsInstanceOf($value, TransportType::class);

        return Json::encode(array_map(fn (TransportType $type) => $type->toString(), $value));
    }

    /** @return TransportType[] */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        $types = Json::decode($value);

        Assertion::allString($types);

        return array_map(fn (string $type) => TransportType::get($type), $types);
    }
}
