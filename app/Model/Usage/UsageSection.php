<?php

declare(strict_types=1);

namespace App\Model\Usage;

use function explode;
use function in_array;

/** Part of the system the usage statistics count separately. */
enum UsageSection: string
{
    case PAYMENTS = 'payments';
    case INVOICES = 'invoices';
    case UNIT_CASHBOOK = 'unit_cashbook';
    case EVENTS = 'events';
    case CAMPS = 'camps';
    case EDUCATION = 'education';
    case TRAVEL = 'travel';
    case SETTINGS = 'settings';
    case OTHER = 'other';

    /**
     * Pages that are no work in the system: the administration, sign-in, errors, the keep-alive ping and
     * the public pages for visitors who are not signed in.
     */
    private const NOT_COUNTED_MODULES = ['Admin', 'Auth', 'Error', 'Error4xx', 'SessionKeepAlive', 'Default', 'Offline', 'Design'];

    private const INVOICE_PRESENTERS = [
        'Payments:InvoiceList',
        'Payments:InvoiceSequence',
        'Payments:InvoiceSequenceList',
        'Settings:Invoices',
    ];

    /** @param string $presenterName full name, e.g. Payments:GroupList */
    public static function fromPresenterName(string $presenterName): ?self
    {
        $module = explode(':', $presenterName)[0];

        if (in_array($module, self::NOT_COUNTED_MODULES, true)) {
            return null;
        }

        if (in_array($presenterName, self::INVOICE_PRESENTERS, true)) {
            return self::INVOICES;
        }

        return match ($module) {
            'Payments' => self::PAYMENTS,
            'Unit' => self::UNIT_CASHBOOK,
            'Events' => self::EVENTS,
            'Camps' => self::CAMPS,
            'Education' => self::EDUCATION,
            'Travel' => self::TRAVEL,
            'Settings' => self::SETTINGS,
            default => self::OTHER,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PAYMENTS => 'Platby',
            self::INVOICES => 'Faktury',
            self::UNIT_CASHBOOK => 'Pokladní kniha jednotky',
            self::EVENTS => 'Akce',
            self::CAMPS => 'Tábory',
            self::EDUCATION => 'Vzdělávací akce',
            self::TRAVEL => 'Cestovní příkazy',
            self::SETTINGS => 'Nastavení',
            self::OTHER => 'Ostatní',
        };
    }
}
