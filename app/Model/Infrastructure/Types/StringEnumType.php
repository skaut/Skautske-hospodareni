<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use Doctrine\DBAL\Types\StringType;

/**
 * BC typ pro introspekci existujícího schématu.
 *
 * Historické migrace vytvářejí enum sloupce s komentářem `(DC2Type:string_enum)`. Runtime hydrataci
 * dnes řeší dedikované typy ({@see AbstractEnumType}), ale introspekce DB (drop/diff) musí legacy
 * komentář stále rozpoznat – proto zůstává zaregistrovaný jako prostý řetězec.
 */
final class StringEnumType extends StringType
{
    public const NAME = 'string_enum';

    public function getName(): string
    {
        return self::NAME;
    }
}
