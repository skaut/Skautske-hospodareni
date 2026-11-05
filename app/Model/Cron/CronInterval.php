<?php

declare(strict_types=1);

namespace App\Model\Cron;

/**
 * Intervals an administrator can choose for a cron job. A fixed list instead of a free cron expression
 * keeps every schedule describable in plain words.
 */
enum CronInterval: string
{
    case EVERY_MINUTE = '* * * * *';
    case EVERY_15_MINUTES = '*/15 * * * *';
    case EVERY_30_MINUTES = '*/30 * * * *';
    case HOURLY = '0 * * * *';
    case EVERY_2_HOURS = '0 */2 * * *';
    case EVERY_4_HOURS = '0 */4 * * *';
    case EVERY_6_HOURS = '0 */6 * * *';
    case EVERY_12_HOURS = '0 */12 * * *';
    case DAILY = '0 6 * * *';

    public function getLabel(): string
    {
        return match ($this) {
            self::EVERY_MINUTE => 'každou minutu',
            self::EVERY_15_MINUTES => 'každých 15 minut',
            self::EVERY_30_MINUTES => 'každých 30 minut',
            self::HOURLY => 'každou hodinu',
            self::EVERY_2_HOURS => 'každé 2 hodiny',
            self::EVERY_4_HOURS => 'každé 4 hodiny',
            self::EVERY_6_HOURS => 'každých 6 hodin',
            self::EVERY_12_HOURS => 'každých 12 hodin',
            self::DAILY => 'jednou denně v 6:00',
        };
    }
}
