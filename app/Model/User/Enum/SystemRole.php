<?php

declare(strict_types=1);

namespace App\Model\User\Enum;

enum SystemRole: string
{
    case ADMIN = 'admin';

    case SUPPORT = 'support';

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'admin',
            self::SUPPORT => 'support',
        };
    }
}
