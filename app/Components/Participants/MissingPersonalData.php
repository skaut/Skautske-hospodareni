<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Model\DTO\Participant\NonMemberParticipant;
use Nette\StaticClass;

use function array_map;
use function implode;

/** Jednotný popis chybějících osobních údajů nečlena pro seznam účastníků i dialog úpravy. */
final class MissingPersonalData
{
    use StaticClass;

    private const LABELS = [
        NonMemberParticipant::MISSING_SEX => 'pohlaví',
        NonMemberParticipant::MISSING_ADDRESS => 'adresa',
    ];

    /** Např. „pohlaví a adresa“, null když nic nechybí. */
    public static function describe(NonMemberParticipant $participant): ?string
    {
        $missing = $participant->getMissingData();

        return $missing === []
            ? null
            : implode(' a ', array_map(static fn (string $data): string => self::LABELS[$data], $missing));
    }
}
