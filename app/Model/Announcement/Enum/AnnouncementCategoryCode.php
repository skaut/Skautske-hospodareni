<?php

declare(strict_types=1);

namespace App\Model\Announcement\Enum;

enum AnnouncementCategoryCode: string
{
    case NEWS = 'news';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case PLAN = 'plan';

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $category) {
            $options[$category->value] = $category->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::NEWS => 'Novinka',
            self::INFO => 'Informace',
            self::WARNING => 'Varování',
            self::ERROR => 'Chyba',
            self::PLAN => 'Plán',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NEWS => 'fi fi-rr-newspaper',
            self::INFO => 'fi fi-rr-info',
            self::WARNING => 'fi fi-rr-triangle-warning',
            self::ERROR => 'fi fi-rr-circle-xmark',
            self::PLAN => 'fi fi-rr-calendar-clock',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::NEWS => 'text-primary',
            self::INFO => 'text-info',
            self::WARNING => 'text-warning',
            self::ERROR => 'text-danger',
            self::PLAN => 'text-secondary',
        };
    }
}
