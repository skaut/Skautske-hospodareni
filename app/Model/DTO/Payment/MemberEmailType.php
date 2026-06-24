<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

enum MemberEmailType: string
{
    case MAIN = 'main';
    case OTHER = 'other';
    case FATHER = 'father';
    case MOTHER = 'mother';
    case UNKNOWN = 'unknown';

    public static function fromSkautis(
        ?string $contactType,
        ?string $parentType = null,
        ?string $contactTypeLabel = null,
    ): self {
        if ($parentType !== null) {
            return match ($parentType) {
                'father' => self::FATHER,
                'mother' => self::MOTHER,
                default => self::UNKNOWN,
            };
        }

        return match ($contactType) {
            'email_hlavni' => self::MAIN,
            'email_dalsi', 'email_jiny' => self::OTHER,
            default => match ($contactTypeLabel) {
                'E-mail (hlavní)' => self::MAIN,
                'E-mail (další)' => self::OTHER,
                default => self::UNKNOWN,
            },
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MAIN => 'Hlavní',
            self::OTHER => 'Další',
            self::FATHER => 'Otec',
            self::MOTHER => 'Matka',
            self::UNKNOWN => 'Ostatní',
        };
    }

    public function isBulkSelectable(): bool
    {
        return match ($this) {
            self::UNKNOWN => false,
            default => true,
        };
    }

    public function isSelectedByDefault(): bool
    {
        return match ($this) {
            self::MAIN => true,
            default => false,
        };
    }
}
